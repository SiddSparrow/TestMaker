<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\Question;
use App\Models\Subject;
use App\Models\QuestionType;
use App\Models\Exam;
use Barryvdh\DomPDF\Facade\Pdf;

class ExamController extends Controller
{
    //
    public function create(){
        return Inertia::render('Exams/Create', [
            'questions' => Question::with(['subject', 'questionType', 'alternatives'])
                ->where('is_active', true)
                ->get(),
            'subjects' => Subject::all(),
            'questionTypes' => QuestionType::all(),
        ]);
    }

    public function store(Request $request){

        $validated = $request->validate([
            'title' => 'required|string',
            'main_subject_id' => 'required|exists:subjects,id',
            'exam_date' => 'nullable|date',
            'description' => 'nullable|string',
            'target_total_points' => 'nullable|integer',
            'header_config' => 'nullable|array',
            'footer_config' => 'nullable|array',
            'topic_distribution' => 'nullable|array',
            'questions' => 'required|array',
            'questions.*.question_id' => 'required|exists:questions,id',
            'questions.*.order' => 'required|integer',
            'questions.*.points_override' => 'nullable|integer',
        ]);

        $exam = Exam::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'exam_date' => $validated['exam_date'],
            'target_total_points' => $validated['target_total_points'],
            'header_config' => $validated['header_config'],
            'footer_config' => $validated['footer_config'],
            'question_distribution' => $validated['topic_distribution'],
            'total_points' => 0, // Será calculado
        ]);
        dd($exam);
        // Adiciona questões
        foreach ($validated['questions'] as $questionData) {
            $exam->questions()->attach($questionData['question_id'], [
                'order' => $questionData['order'],
                'points_override' => $questionData['points_override'],
            ]);
        }

        // Atualiza total de pontos
        $exam->update([
            'total_points' => $exam->questions->sum(function($q) {
                return $q->pivot->points_override ?? $q->points;
            })
        ]);

        return redirect()->route('exams.show', $exam);
    }

    public function show(Exam $exam){
        $exam->load(['questions.subject', 'questions.questionType']);
        return Inertia::render('Exams/Show', [
            'exam' => $exam,
        ]);
    }

    public function exportPdf(Exam $exam, Request $request)
{
    $exam->load(['questions' => function($query) {
        $query->orderBy('exam_questions.order');
    }, 'questions.alternatives']);
    
    $withAnswers = $request->boolean('with_answers');
    
    $pdf = Pdf::loadView('exams.pdf', [
        'exam' => $exam,
        'questions' => $exam->questions,
        'showAnswers' => $withAnswers
    ]);
    
    $filename = Str::slug($exam->title) . ($withAnswers ? '-gabarito' : '') . '.pdf';
    
    return $pdf->download($filename);
}

    public function exportDocx(Exam $exam, Request $request)
    {
        $exam->load(['questions.alternatives']);
        $withAnswers = $request->boolean('with_answers');
        
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();
        
        // Header
        $section->addText($exam->header_config['school_name'] ?? '', ['bold' => true, 'size' => 16], ['alignment' => 'center']);
        $section->addText($exam->title, ['bold' => true, 'size' => 14], ['alignment' => 'center']);
        
        // Questions
        foreach ($exam->questions as $index => $question) {
            $section->addText(($index + 1) . '. ' . $question->statement, ['size' => 12]);
            
            if ($question->alternatives->count() > 0) {
                foreach ($question->alternatives as $altIndex => $alt) {
                    $letter = chr(65 + $altIndex);
                    $text = $letter . ') ' . $alt->content;
                    if ($withAnswers && $alt->is_correct) {
                        $text .= ' [CORRETA]';
                    }
                    $section->addText($text, ['size' => 11]);
                }
            }
            
            $section->addTextBreak();
        }
        
        $filename = Str::slug($exam->title) . ($withAnswers ? '-gabarito' : '') . '.docx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save('php://output');
        exit;
    }
}
