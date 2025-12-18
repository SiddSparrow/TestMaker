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
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
class ExamController extends Controller
{

    use AuthorizesRequests;

    public function index(){
        
        $exams = Exam::with([
            'questions' => function($query) {
                $query->with('alternatives')
                    ->orderBy('order', 'asc');
            }
        ])
        ->withCount('questions') // Isso adiciona uma propriedade 'questions_count'
        ->where('user_id', auth()->id())
        ->get();

        return Inertia::render('Exams/Index', [
            'exams' => $exams,
            'questionTypes' => QuestionType::all(),
        ]);
    }
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
        //dd($exam);
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

        return redirect()->route('exams.index', $exam)->with('success', 'Exame criado com sucesso!');
    }

    public function show(Exam $exam){
        $exam->load(['questions.subject', 'questions.questionType']);
        return Inertia::render('Exams/Show', [
            'exam' => $exam,
        ]);
    }

    public function exportPdf(Exam $exam, Request $request){
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


    public function exportDocx(Exam $exam, Request $request){
        $this->authorize('view', $exam); // Segurança
        
        // Carrega relações com ordenação
        $exam->load(['questions' => function($query) {
            $query->orderBy('exam_questions.order');
        }, 'questions.alternatives']);
        
        $withAnswers = $request->boolean('with_answers', false);
        
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        
        // Configurações do documento
        $phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language('pt-BR'));
        
        $section = $phpWord->addSection([
            'marginTop' => 1000,
            'marginBottom' => 1000,
            'marginLeft' => 1000,
            'marginRight' => 1000,
        ]);
        
        // Header
        if (!empty($exam->header_config['school_name'])) {
            $section->addText(
                $exam->header_config['school_name'],
                ['bold' => true, 'size' => 16, 'name' => 'Arial'],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]
            );
        }
        
        $section->addText(
            $exam->title,
            ['bold' => true, 'size' => 14, 'name' => 'Arial'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]
        );
        
        if ($exam->description) {
            $section->addText(
                $exam->description,
                ['size' => 11, 'name' => 'Arial'],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]
            );
        }
        
        if (!empty($exam->header_config['show_date']) && $exam->exam_date) {
            $section->addText(
                'Data: ' . $exam->exam_date->format('d/m/Y'),
                ['size' => 11, 'name' => 'Arial'],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]
            );
        }
        
        // Campos do aluno
        if (!empty($exam->header_config['show_student_info'])) {
            $section->addTextBreak(1);
            $section->addText('Nome: ________________________________________________', ['size' => 11]);
            $section->addText('Turma: _____________ Data: ___/___/___ Nota: _______', ['size' => 11]);
        }
        
        // Total de pontos
        $section->addText(
            'Valor Total: ' . $exam->total_points . ' pontos',
            ['bold' => true, 'size' => 11],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
        
        // Linha separadora
        $section->addLine([
            'weight' => 1,
            'width' => 450,
            'height' => 0,
            'color' => '000000'
        ]);
        
        $section->addTextBreak(1);
        
        // Questões
        foreach ($exam->questions as $index => $question) {
            $points = $question->pivot->points_override ?? $question->points;
            
            // Número e enunciado da questão
            $questionRun = $section->addTextRun(['spaceAfter' => 100]);
            $questionRun->addText(
                ($index + 1) . '. ',
                ['bold' => true, 'size' => 12, 'name' => 'Arial']
            );
            $questionRun->addText(
                $question->statement . ' ',
                ['size' => 12, 'name' => 'Arial']
            );
            $questionRun->addText(
                '(' . $points . ' ' . ($points == 1 ? 'ponto' : 'pontos') . ')',
                ['size' => 10, 'italic' => true, 'name' => 'Arial']
            );
            
            // Alternativas
            if ($question->alternatives->count() > 0) {
                foreach ($question->alternatives as $altIndex => $alt) {
                    $letter = chr(65 + $altIndex);
                    
                    $altRun = $section->addTextRun(['indentation' => ['left' => 360]]);
                    $altRun->addText($letter . ') ', ['bold' => true, 'size' => 11]);
                    $altRun->addText($alt->content, ['size' => 11]);
                    
                    if ($withAnswers && $alt->is_correct) {
                        $altRun->addText(' [CORRETA]', [
                            'bold' => true,
                            'color' => '008000',
                            'size' => 11
                        ]);
                    }
                }
            } else {
                // Espaço para resposta dissertativa
                for ($i = 0; $i < 4; $i++) {
                    $section->addText(
                        '_____________________________________________',
                        ['size' => 11],
                        ['indentation' => ['left' => 360]]
                    );
                }
            }
            
            // Explicação (se mostrar gabarito)
            if ($withAnswers && $question->explanation) {
                $section->addTextBreak(0);
                $explRun = $section->addTextRun(['indentation' => ['left' => 360]]);
                $explRun->addText('Explicação: ', ['bold' => true, 'size' => 10, 'italic' => true]);
                $explRun->addText($question->explanation, ['size' => 10, 'italic' => true]);
            }
            
            $section->addTextBreak(1);
        }
        
        // Linha separadora final
        $section->addLine([
            'weight' => 1,
            'width' => 450,
            'height' => 0,
            'color' => '000000'
        ]);
        
        // Footer
        if (!empty($exam->footer_config['custom_text'])) {
            $section->addText(
                $exam->footer_config['custom_text'],
                ['size' => 11, 'name' => 'Arial'],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
            );
        }
        
        // Preparar download
        $filename = Str::slug($exam->title) . ($withAnswers ? '-gabarito' : '') . '.docx';
        
        // Headers para download
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1'); // IE
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
        
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save('php://output');
        exit;
    }
}
