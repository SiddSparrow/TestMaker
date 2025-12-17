<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Question;
use App\Models\Subject;
use App\Models\QuestionType;
use App\Models\Exam;
class ExamController extends Controller
{
    //
    public function create(){
        return Inertia::render('Exams/Create', [
            'questions' => Question::with(['subject', 'questionType'])
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
}
