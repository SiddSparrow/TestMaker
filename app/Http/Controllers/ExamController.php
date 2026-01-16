<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\Question;
use App\Models\Subject;
use App\Models\QuestionType;
use App\Models\Exam;
use App\Models\Topic;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExamController extends Controller
{

    use AuthorizesRequests;

    public function index()
    {
        $exams = Exam::with([
            'subject',
            'user',
            'questions' => function ($query) {
                $query->orderBy('exam_questions.order');
            }
        ])
            ->withCount('questions')
            ->byUser(auth()->id())
            ->latest()
            ->get(); // ou ->paginate(15)

        // DEBUG: Adicione isso temporariamente
        Log::info('Exams carregados:', [
            'primeiro_exam' => $exams->first(),
        ]);

        return Inertia::render('Exams/Index', [
            'exams' => $exams,
            'questionTypes' => QuestionType::all(),
        ]);
    }
    //
    /* public function create()
    {
        return Inertia::render('Exams/Create', [
            'questions' => Question::with(['subject', 'questionType', 'alternatives', 'topic'])
                ->where('is_active', true)
                ->get(),
            'subjects' => Subject::all(),
            'questionTypes' => QuestionType::all(),
            'topics' => Topic::all(),
        ]);
    } */

    /* public function store(Request $request)
    {

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
            'total_points' => $exam->questions->sum(function ($q) {
                return $q->pivot->points_override ?? $q->points;
            })
        ]);

        return redirect()->route('exams.index', $exam)->with('success', 'Exame criado com sucesso!');
    }

    public function show(Exam $exam)
    {
        // Autorizar acesso
        $this->authorize('view', $exam);

        // Carregar todas as relações necessárias
        $exam->load([
            'questions' => function ($query) {
                $query->with([
                    'subject:id,name,color',
                    'topic:id,name',
                    'questionType:id,name',
                    'alternatives' => function ($q) {
                        $q->orderBy('order');
                    },
                    'tags:id,name'
                ])->orderBy('exam_questions.order');
            },

        ]);

        // Carregar tipos de questão para o preview
        $questionTypes = QuestionType::all();

        return Inertia::render('Exams/Show', [
            'exam' => $exam,
            'questionTypes' => $questionTypes,
        ]);
    }
    public function update(Request $request, Exam $exam)
    {
        $this->authorize('update', $exam);
        //dd($request->all());
        $data = $request->all();

        if (isset($data['header_config']) && is_string($data['header_config'])) {
            $data['header_config'] = json_decode($data['header_config'], true);
        }

        if (isset($data['footer_config']) && is_string($data['footer_config'])) {
            $data['footer_config'] = json_decode($data['footer_config'], true);
        }

        // Substituir a request com dados decodificados
        $request->replace($data);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'exam_date' => 'nullable|date',
            'target_total_points' => 'nullable|integer',
            'header_config' => 'nullable|array',
            'footer_config' => 'nullable|array',
            'questions' => 'required|array|min:1',
            'questions.*.question_id' => 'required|exists:questions,id',
            'questions.*.order' => 'required|integer',
            'questions.*.points_override' => 'nullable|integer|min:1|max:10',
        ]);

        DB::beginTransaction();

        try {
            // Atualiza dados da prova
            $exam->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'exam_date' => $validated['exam_date'] ?? null,
                'target_total_points' => $validated['target_total_points'] ?? null,
                'header_config' => $validated['header_config'] ?? [],
                'footer_config' => $validated['footer_config'] ?? [],
            ]);

            // Sincroniza questões (remove antigas e adiciona novas)
            $exam->questions()->detach();

            foreach ($validated['questions'] as $questionData) {
                $exam->questions()->attach($questionData['question_id'], [
                    'order' => $questionData['order'],
                    'points_override' => $questionData['points_override'] ?? null,
                ]);
            }

            // Recalcula total de pontos
            $exam->load('questions');
            $totalPoints = $exam->questions->sum(function ($q) {
                return $q->pivot->points_override ?? $q->points;
            });

            $exam->update(['total_points' => $totalPoints]);

            DB::commit();

            return redirect()->back()->with('success', 'Prova atualizada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erro ao atualizar prova', [
                'exam_id' => $exam->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Erro ao atualizar prova: ' . $e->getMessage());
        }
    } */


    /* public function edit(Exam $exam)
    {
        $this->authorize('update', $exam);

        $exam->load(['questions' => function ($query) {
            $query->with(['subject', 'questionType', 'alternatives', 'topic', 'tags'])
                ->orderBy('exam_questions.order');
        }]);

        return inertia('Exams/Edit', [
            'exam' => $exam,
            'questions' => Question::with(['subject', 'questionType', 'alternatives'])
                ->where('user_id', auth()->id())
                ->where('is_active', true)
                ->get(),
            'subjects' => Subject::where('user_id', auth()->id())->get(),
            'topics' => Topic::where('user_id', auth()->id())->get(),
            'questionTypes' => QuestionType::all(),
        ]);
    } */


    /*    public function update(Request $request, Exam $exam)
{
    $this->authorize('update', $exam);
    
    // Debug 1: Verificar todos os dados recebidos
    \Log::info('Exam Update - Request Data:', [
        'exam_id' => $exam->id,
        'request_all' => $request->all(),
        'exam_before' => $exam->toArray()
    ]);

     // Decodificar JSONs se forem strings
    $data = $request->all();
    
    if (isset($data['header_config']) && is_string($data['header_config'])) {
        $data['header_config'] = json_decode($data['header_config'], true);
    }
    
    if (isset($data['footer_config']) && is_string($data['footer_config'])) {
        $data['footer_config'] = json_decode($data['footer_config'], true);
    }
    
    // Substituir a request com dados decodificados
    $request->replace($data);
    
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'exam_date' => 'nullable|date',
        'target_total_points' => 'nullable|integer',
        'header_config' => 'nullable|array',
        'footer_config' => 'nullable|array',
        'questions' => 'required|array|min:1',
        'questions.*.question_id' => 'required|exists:questions,id',
        'questions.*.order' => 'required|integer',
        'questions.*.points_override' => 'nullable|integer|min:1|max:10',
    ]);
    //dd($validated);
    // Debug 2: Verificar dados validados
    \Log::info('Exam Update - Validated Data:', [
        'exam_id' => $exam->id,
        'validated' => $validated,
        'questions_count' => count($validated['questions'] ?? [])
    ]);
    
    DB::beginTransaction();
    
    try {
        // Debug 3: Antes de atualizar
        \Log::info('Exam Update - Before Update:', [
            'exam_id' => $exam->id,
            'old_title' => $exam->title,
            'old_total_points' => $exam->total_points
        ]);
        
        // Atualiza dados da prova
        $updateData = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'exam_date' => $validated['exam_date'] ?? null,
            'target_total_points' => $validated['target_total_points'] ?? null,
            'header_config' => $validated['header_config'] ?? [],
            'footer_config' => $validated['footer_config'] ?? [],
        ];
        
        \Log::info('Exam Update - Update Data:', $updateData);
        
        $exam->update($updateData);
        
        // Debug 4: Verificar questões atuais antes de sincronizar
        $currentQuestions = $exam->questions()->pluck('questions.id')->toArray();
        \Log::info('Exam Update - Current Questions:', [
            'exam_id' => $exam->id,
            'current_question_ids' => $currentQuestions,
            'current_count' => count($currentQuestions)
        ]);
        
        // Sincroniza questões (remove antigas e adiciona novas)
        $exam->questions()->detach();
        
        // Debug 5: Depois de detach
        $afterDetachQuestions = $exam->questions()->pluck('questions.id')->toArray();
        \Log::info('Exam Update - After Detach:', [
            'exam_id' => $exam->id,
            'question_ids_after_detach' => $afterDetachQuestions
        ]);
        
        // Debug 6: Verificar cada questão a ser adicionada
        $questionsToAttach = [];
        foreach ($validated['questions'] as $index => $questionData) {
            \Log::info("Exam Update - Processing Question {$index}:", [
                'question_id' => $questionData['question_id'],
                'order' => $questionData['order'],
                'points_override' => $questionData['points_override'] ?? 'null',
                'question_exists' => \App\Models\Question::where('id', $questionData['question_id'])->exists()
            ]);
            
            $questionsToAttach[$questionData['question_id']] = [
                'order' => $questionData['order'],
                'points_override' => $questionData['points_override'] ?? null,
            ];
        }
        
        // Debug 7: Antes de attach
        \Log::info('Exam Update - Before Attach:', [
            'exam_id' => $exam->id,
            'questions_to_attach' => $questionsToAttach,
            'count_to_attach' => count($questionsToAttach)
        ]);
        
        // Usar sync em vez de attach individual
        $exam->questions()->sync($questionsToAttach);
        
        // Debug 8: Depois de attach
        $afterAttachQuestions = $exam->questions()->withPivot('order', 'points_override')->get();
        \Log::info('Exam Update - After Attach:', [
            'exam_id' => $exam->id,
            'attached_questions_count' => $afterAttachQuestions->count(),
            'attached_questions' => $afterAttachQuestions->map(function($q) {
                return [
                    'id' => $q->id,
                    'order' => $q->pivot->order,
                    'points_override' => $q->pivot->points_override
                ];
            })->toArray()
        ]);
        
        // Recalcula total de pontos
        $exam->load('questions');
        $totalPoints = $exam->questions->sum(function($q) {
            $points = $q->pivot->points_override ?? $q->points;
            \Log::info('Exam Update - Calculating Points for Question:', [
                'question_id' => $q->id,
                'points' => $q->points,
                'pivot_points_override' => $q->pivot->points_override,
                'calculated_points' => $points
            ]);
            return $points;
        });
        
        \Log::info('Exam Update - Total Points Calculation:', [
            'exam_id' => $exam->id,
            'total_points' => $totalPoints
        ]);
        
        $exam->update(['total_points' => $totalPoints]);
        
        // Debug 9: Verificar exame após atualização
        $exam->refresh();
        \Log::info('Exam Update - After Complete Update:', [
            'exam_id' => $exam->id,
            'new_title' => $exam->title,
            'new_total_points' => $exam->total_points,
            'questions_count' => $exam->questions()->count()
        ]);
        
        DB::commit();
        
        \Log::info('Exam Update - Success:', [
            'exam_id' => $exam->id,
            'status' => 'updated_successfully'
        ]);
        
        return redirect()->back()->with('success', 'Prova atualizada com sucesso!');
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        // Debug 10: Erro detalhado
        \Log::error('Exam Update - Error:', [
            'exam_id' => $exam->id,
            'error_message' => $e->getMessage(),
            'error_trace' => $e->getTraceAsString(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'request_data' => $request->all()
        ]);
        
        return back()
            ->withInput()
            ->withErrors(['error' => 'Erro ao atualizar prova: ' . $e->getMessage()])
            ->with('error', 'Erro ao atualizar prova. Verifique os logs.');
    }
} */
    public function create()
    {
        return Inertia::render('Exams/Create', [
            'questions' => Question::with(['subject', 'questionType', 'alternatives', 'topic'])
                ->where('user_id', auth()->id())
                ->where('is_active', true)
                ->get(),
            'subjects' => Subject::all(),
            'questionTypes' => QuestionType::all(),
            'topics' => Topic::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Informações básicas
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'exam_date' => 'nullable|date',
            'main_subject_id' => 'required|exists:subjects,id',

            // Configurações de cabeçalho (JSON)
            'header_config' => 'nullable|array',
            'header_config.school_name' => 'nullable|string|max:255',
            'header_config.show_date' => 'nullable|boolean',
            'header_config.show_student_info' => 'nullable|boolean',
            'header_config.show_logo' => 'nullable|boolean',

            // Configurações de formatação (JSON)
            'format_config' => 'nullable|array',
            'format_config.font_size' => 'nullable|string|in:10pt,11pt,12pt,14pt',
            'format_config.font_family' => 'nullable|string|in:Arial,Times New Roman,Calibri,Georgia',
            'format_config.line_spacing' => 'nullable|string|in:1.0,1.15,1.5,2.0',
            'format_config.justify_text' => 'nullable|boolean',
            'format_config.columns' => 'nullable|integer|min:1|max:2',
            'format_config.margins' => 'nullable|string|in:narrow,normal,wide',
            'format_config.orientation' => 'nullable|string|in:portrait,landscape',
            'format_config.paper_size' => 'nullable|string|in:A4,Letter',
            'format_config.show_question_points' => 'nullable|boolean',
            'format_config.shuffle_questions' => 'nullable|boolean',
            'format_config.shuffle_alternatives' => 'nullable|boolean',
            'format_config.show_answer_space' => 'nullable|boolean',
            'format_config.separate_answer_sheet' => 'nullable|boolean',

            // Configurações de rodapé (JSON)
            'footer_config' => 'nullable|array',
            'footer_config.custom_text' => 'nullable|string|max:500',
            'footer_config.show_page_number' => 'nullable|boolean',

            // Distribuições (JSON)
            'difficulty_distribution' => 'nullable|array',
            'difficulty_distribution.easy' => 'nullable|integer|min:0',
            'difficulty_distribution.medium' => 'nullable|integer|min:0',
            'difficulty_distribution.hard' => 'nullable|integer|min:0',

            'topic_distribution' => 'nullable|array',
            'topic_distribution.*.topic_id' => 'required|exists:topics,id',
            'topic_distribution.*.question_count' => 'required|integer|min:1',

            // Targets
            'target_total_points' => 'nullable|numeric|min:0',
            'target_question_count' => 'nullable|integer|min:1',

            // Questões selecionadas
            'questions' => 'required|array|min:1',
            'questions.*.question_id' => 'required|exists:questions,id',
            'questions.*.order' => 'required|integer|min:1',
            'questions.*.points_override' => 'nullable|numeric|min:0',
        ]);

        // Criar a prova
        $exam = Exam::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'exam_date' => $validated['exam_date'] ?? null,
            'main_subject_id' => $validated['main_subject_id'],

            // Configurações de layout
            'header_config' => $validated['header_config'] ?? [],
            'format_config' => $validated['format_config'] ?? [],
            'footer_config' => $validated['footer_config'] ?? [],

            // Distribuições
            'difficulty_distribution' => $validated['difficulty_distribution'] ?? [],
            'topic_distribution' => $validated['topic_distribution'] ?? [],

            // Targets
            'target_total_points' => $validated['target_total_points'] ?? null,
            'target_question_count' => $validated['target_question_count'] ?? null,

            'total_points' => 0, // Será calculado depois
        ]);

        // Anexar questões com ordem e pontos customizados
        foreach ($validated['questions'] as $questionData) {
            $exam->questions()->attach($questionData['question_id'], [
                'order' => $questionData['order'],
                'points_override' => $questionData['points_override'] ?? null,
            ]);
        }

        // Recalcular total de pontos
        $exam->recalculateTotalPoints();

        return redirect()
            ->route('exams.show', $exam)
            ->with('success', 'Prova criada com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Exam $exam)
    {
        // Autorizar acesso
        $this->authorize('view', $exam);

        // Carregar todas as relações necessárias
        $exam->load([
            'subject:id,name,color',
            'user:id,name',
            'questions' => function ($query) {
                $query->with([
                    'subject:id,name,color',
                    'topic:id,name',
                    'questionType:id,name',
                    'alternatives' => function ($q) {
                        $q->orderBy('order');
                    },
                    'tags:id,name,slug'
                ])->orderBy('exam_questions.order');
            },
        ]);

        // Carregar tipos de questão para o preview
        $questionTypes = QuestionType::all();

        return Inertia::render('Exams/Show', [
            'exam' => $exam,
            'questionTypes' => $questionTypes,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Exam $exam)
    {
        $this->authorize('update', $exam);

        $exam->load([
            'questions' => function ($query) {
                $query->with(['subject', 'questionType', 'alternatives', 'topic'])
                    ->orderBy('exam_questions.order');
            }
        ]);

        return Inertia::render('Exams/Edit', [
            'exam' => $exam,
            'questions' => Question::with(['subject', 'questionType', 'alternatives', 'topic'])
                ->where('user_id', auth()->id())
                ->where('is_active', true)
                ->get(),
            'subjects' => Subject::all(),
            'questionTypes' => QuestionType::all(),
            'topics' => Topic::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Exam $exam)
    {
        $this->authorize('update', $exam);

        $validated = $request->validate([
            // Informações básicas
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'exam_date' => 'nullable|date',
            'main_subject_id' => 'required|exists:subjects,id',

            // Configurações de layout
            'header_config' => 'nullable|array',
            'header_config.school_name' => 'nullable|string|max:255',
            'header_config.show_date' => 'nullable|boolean',
            'header_config.show_student_info' => 'nullable|boolean',
            'header_config.show_logo' => 'nullable|boolean',

            'format_config' => 'nullable|array',
            'format_config.font_size' => 'nullable|string|in:10pt,11pt,12pt,14pt',
            'format_config.font_family' => 'nullable|string|in:Arial,Times New Roman,Calibri,Georgia',
            'format_config.line_spacing' => 'nullable|string|in:1.0,1.15,1.5,2.0',
            'format_config.justify_text' => 'nullable|boolean',
            'format_config.columns' => 'nullable|integer|min:1|max:2',
            'format_config.margins' => 'nullable|string|in:narrow,normal,wide',
            'format_config.orientation' => 'nullable|string|in:portrait,landscape',
            'format_config.paper_size' => 'nullable|string|in:A4,Letter',
            'format_config.show_question_points' => 'nullable|boolean',
            'format_config.shuffle_questions' => 'nullable|boolean',
            'format_config.shuffle_alternatives' => 'nullable|boolean',
            'format_config.show_answer_space' => 'nullable|boolean',
            'format_config.separate_answer_sheet' => 'nullable|boolean',

            'footer_config' => 'nullable|array',
            'footer_config.custom_text' => 'nullable|string|max:500',
            'footer_config.show_page_number' => 'nullable|boolean',

            'difficulty_distribution' => 'nullable|array',
            'difficulty_distribution.easy' => 'nullable|integer|min:0',
            'difficulty_distribution.medium' => 'nullable|integer|min:0',
            'difficulty_distribution.hard' => 'nullable|integer|min:0',

            'topic_distribution' => 'nullable|array',
            'topic_distribution.*.topic_id' => 'required|exists:topics,id',
            'topic_distribution.*.question_count' => 'required|integer|min:1',

            'target_total_points' => 'nullable|numeric|min:0',
            'target_question_count' => 'nullable|integer|min:1',

            // Questões
            'questions' => 'required|array|min:1',
            'questions.*.question_id' => 'required|exists:questions,id',
            'questions.*.order' => 'required|integer|min:1',
            'questions.*.points_override' => 'nullable|numeric|min:0',
        ]);

        // Atualizar a prova
        $exam->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'exam_date' => $validated['exam_date'] ?? null,
            'main_subject_id' => $validated['main_subject_id'],
            'header_config' => $validated['header_config'] ?? [],
            'format_config' => $validated['format_config'] ?? [],
            'footer_config' => $validated['footer_config'] ?? [],
            'difficulty_distribution' => $validated['difficulty_distribution'] ?? [],
            'topic_distribution' => $validated['topic_distribution'] ?? [],
            'target_total_points' => $validated['target_total_points'] ?? null,
            'target_question_count' => $validated['target_question_count'] ?? null,
        ]);

        // Sincronizar questões (remove antigas, adiciona novas)
        $questionsToSync = [];
        foreach ($validated['questions'] as $questionData) {
            $questionsToSync[$questionData['question_id']] = [
                'order' => $questionData['order'],
                'points_override' => $questionData['points_override'] ?? null,
            ];
        }
        $exam->questions()->sync($questionsToSync);

        // Recalcular total de pontos
        $exam->recalculateTotalPoints();

        return redirect()
            ->route('exams.show', $exam)
            ->with('success', 'Prova atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Exam $exam)
    {
        $this->authorize('delete', $exam);

        $exam->questions()->detach();
        $exam->delete();

        return redirect()
            ->route('exams.index')
            ->with('success', 'Prova excluída com sucesso!');
    }

    /**
     * Publish/Unpublish exam
     */
    public function togglePublish(Exam $exam)
    {
        $this->authorize('update', $exam);

        $exam->update([
            'is_published' => !$exam->is_published,
            'published_at' => !$exam->is_published ? now() : null,
        ]);

        return back()->with(
            'success',
            $exam->is_published ? 'Prova publicada com sucesso!' : 'Prova despublicada com sucesso!'
        );
    }

    /**
     * Duplicate exam
     */
    public function duplicate(Exam $exam)
    {
        $this->authorize('view', $exam);

        // Criar cópia da prova
        $newExam = $exam->replicate();
        $newExam->title = $exam->title . ' (Cópia)';
        $newExam->is_published = false;
        $newExam->published_at = null;
        $newExam->save();

        // Copiar questões com suas configurações
        foreach ($exam->questions as $question) {
            $newExam->questions()->attach($question->id, [
                'order' => $question->pivot->order,
                'points_override' => $question->pivot->points_override,
            ]);
        }

        // Recalcular pontos
        $newExam->recalculateTotalPoints();

        return redirect()
            ->route('exams.edit', $newExam)
            ->with('success', 'Prova duplicada com sucesso! Edite conforme necessário.');
    }

    /* public function exportPdf(Exam $exam, Request $request)
    {
        $exam->load(['questions' => function ($query) {
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
        $this->authorize('view', $exam); // Segurança

        // Carrega relações com ordenação
        $exam->load(['questions' => function ($query) {
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
    } */

    public function exportPdf(Exam $exam, Request $request)
{
    $this->authorize('view', $exam);

    // DEBUG: Adicione isso temporariamente
    Log::info('Exportando PDF:', [
        'exam_id' => $exam->id,
        'format_config' => $exam->format_config,
        'format_config_type' => gettype($exam->format_config),
        'header_config' => $exam->header_config,
    ]);

    // Carregar relações
    $exam->load([
        'subject',
        'questions' => function ($query) use ($exam) {
            $query->orderBy('exam_questions.order');
            
            // Embaralhar se configurado
            if (($exam->format_config['shuffle_questions'] ?? false) == true) {
                $query->inRandomOrder();
            }
        },
        'questions.alternatives' => function ($query) {
            $query->orderBy('order');
        }
    ]);

    $withAnswers = $request->boolean('with_answers', false);

    // Preparar questões (embaralhar alternativas se necessário)
    $questions = $exam->questions;
    
    if (($exam->format_config['shuffle_alternatives'] ?? false) == true) {
        $questions = $questions->map(function ($question) {
            $question->alternatives = $question->alternatives->shuffle();
            return $question;
        });
    }

    // DEBUG: Verifique o que está sendo passado
    Log::info('Dados para PDF:', [
        'exam_title' => $exam->title,
        'format_config_fonte' => $exam->format_config['font_family'] ?? 'não definido',
        'format_config_tamanho' => $exam->format_config['font_size'] ?? 'não definido',
        'colunas' => $exam->format_config['columns'] ?? 'não definido',
    ]);

    // Gerar PDF
    $pdf = Pdf::loadView('exams.pdf', [
        'exam' => $exam,              // ← Passa o exam completo (com format_config, header_config, etc)
        'questions' => $questions,
        'showAnswers' => $withAnswers,
    ])
    ->setPaper(
        $exam->format_config['paper_size'] ?? 'A4', 
        $exam->format_config['orientation'] ?? 'portrait'
    );

    // Margens
    $marginValue = $this->getMarginValue($exam->format_config['margins'] ?? 'normal');
    $pdf->setOption('margin-top', $marginValue)
        ->setOption('margin-bottom', $marginValue)
        ->setOption('margin-left', $marginValue)
        ->setOption('margin-right', $marginValue);

    $filename = Str::slug($exam->title) . ($withAnswers ? '-gabarito' : '') . '.pdf';

    return $pdf->download($filename);
}

    /**
     * Export exam to DOCX
     */
    public function exportDocx(Exam $exam, Request $request)
    {
        $this->authorize('view', $exam);

        // Carregar relações
        $exam->load([
            'subject',
            'questions' => function ($query) use ($exam) {
                $query->orderBy('exam_questions.order');

                if ($exam->format_config['shuffle_questions'] ?? false) {
                    $query->inRandomOrder();
                }
            },
            'questions.alternatives' => function ($query) {
                $query->orderBy('order');
            }
        ]);

        $withAnswers = $request->boolean('with_answers', false);

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language('pt-BR'));

        // Pegar configurações
        $config = $exam->format_config ?? [];
        $headerConfig = $exam->header_config ?? [];
        $footerConfig = $exam->footer_config ?? [];

        // Configurar página
        $sectionStyle = [
            'marginTop' => $this->getMarginValueTwips($config['margins'] ?? 'normal'),
            'marginBottom' => $this->getMarginValueTwips($config['margins'] ?? 'normal'),
            'marginLeft' => $this->getMarginValueTwips($config['margins'] ?? 'normal'),
            'marginRight' => $this->getMarginValueTwips($config['margins'] ?? 'normal'),
        ];

        // Orientação
        if (($config['orientation'] ?? 'portrait') === 'landscape') {
            $sectionStyle['orientation'] = 'landscape';
        }

        $section = $phpWord->addSection($sectionStyle);

        // Configurar colunas se necessário
        if (($config['columns'] ?? 1) === 2) {
            $section->addColumnBreak();
        }

        // Extrair tamanho da fonte
        $fontSize = (int) str_replace('pt', '', $config['font_size'] ?? '12pt');
        $fontFamily = $config['font_family'] ?? 'Arial';
        $lineSpacing = (float) ($config['line_spacing'] ?? '1.5') * 240; // Converter para twips

        // ====================
        // CABEÇALHO
        // ====================

        // Logo (placeholder se habilitado)
        if ($headerConfig['show_logo'] ?? false) {
            $section->addText(
                '[LOGO DA ESCOLA]',
                ['size' => 10, 'color' => '666666', 'name' => $fontFamily],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 100]
            );
        }

        // Nome da escola
        if (!empty($headerConfig['school_name'])) {
            $section->addText(
                $headerConfig['school_name'],
                ['bold' => true, 'size' => $fontSize + 4, 'name' => $fontFamily],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]
            );
        }

        // Título da prova
        $section->addText(
            $exam->title,
            ['bold' => true, 'size' => $fontSize + 2, 'name' => $fontFamily],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]
        );

        // Descrição
        if ($exam->description) {
            $section->addText(
                $exam->description,
                ['size' => $fontSize - 1, 'name' => $fontFamily],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]
            );
        }

        // Data da prova
        if (($headerConfig['show_date'] ?? true) && $exam->exam_date) {
            $section->addText(
                'Data: ' . $exam->exam_date->format('d/m/Y'),
                ['size' => $fontSize - 1, 'name' => $fontFamily],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]
            );
        }

        // Campos do aluno
        if ($headerConfig['show_student_info'] ?? true) {
            $section->addTextBreak(1);
            $section->addText(
                'Nome: ________________________________________________',
                ['size' => $fontSize - 1, 'name' => $fontFamily]
            );
            $section->addText(
                'Turma: _____________ Data: ___/___/___ Nota: _______',
                ['size' => $fontSize - 1, 'name' => $fontFamily]
            );
        }

        // Total de pontos
        $section->addTextBreak(1);
        $section->addText(
            'Valor Total: ' . $exam->total_points . ' pontos',
            ['bold' => true, 'size' => $fontSize, 'name' => $fontFamily],
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

        // ====================
        // QUESTÕES
        // ====================

        $questions = $exam->questions;

        // Embaralhar alternativas se configurado
        if ($config['shuffle_alternatives'] ?? false) {
            $questions = $questions->map(function ($question) {
                $question->alternatives = $question->alternatives->shuffle();
                return $question;
            });
        }

        foreach ($questions as $index => $question) {
            $points = $question->pivot->points_override ?? $question->points;

            // Alinhamento do texto
            $paragraphStyle = [
                'spaceAfter' => 100,
                'lineHeight' => $lineSpacing,
            ];

            if ($config['justify_text'] ?? false) {
                $paragraphStyle['alignment'] = \PhpOffice\PhpWord\SimpleType\Jc::BOTH;
            }

            // Número e enunciado da questão
            $questionRun = $section->addTextRun($paragraphStyle);
            $questionRun->addText(
                ($index + 1) . '. ',
                ['bold' => true, 'size' => $fontSize, 'name' => $fontFamily]
            );
            $questionRun->addText(
                $question->statement . ' ',
                ['size' => $fontSize, 'name' => $fontFamily]
            );

            // Mostrar pontos se configurado
            if ($config['show_question_points'] ?? true) {
                $questionRun->addText(
                    '(' . $points . ' ' . ($points == 1 ? 'ponto' : 'pontos') . ')',
                    ['size' => $fontSize - 2, 'italic' => true, 'name' => $fontFamily]
                );
            }

            // Alternativas (múltipla escolha)
            if ($question->alternatives->count() > 0) {
                foreach ($question->alternatives as $altIndex => $alt) {
                    $letter = chr(65 + $altIndex);

                    $altRun = $section->addTextRun([
                        'indentation' => ['left' => 360],
                        'lineHeight' => $lineSpacing,
                    ]);
                    $altRun->addText(
                        $letter . ') ',
                        ['bold' => true, 'size' => $fontSize - 1, 'name' => $fontFamily]
                    );
                    $altRun->addText(
                        $alt->content,
                        ['size' => $fontSize - 1, 'name' => $fontFamily]
                    );

                    if ($withAnswers && $alt->is_correct) {
                        $altRun->addText(' ✓ [CORRETA]', [
                            'bold' => true,
                            'color' => '008000',
                            'size' => $fontSize - 1,
                            'name' => $fontFamily
                        ]);
                    }
                }
            }
            // Espaço para resposta (dissertativa)
            else if ($config['show_answer_space'] ?? true) {
                for ($i = 0; $i < 4; $i++) {
                    $section->addText(
                        '_____________________________________________',
                        ['size' => $fontSize - 1, 'name' => $fontFamily],
                        ['indentation' => ['left' => 360]]
                    );
                }
            }

            // Explicação (se mostrar gabarito)
            if ($withAnswers && $question->explanation) {
                $section->addTextBreak(0);
                $explRun = $section->addTextRun(['indentation' => ['left' => 360]]);
                $explRun->addText(
                    'Explicação: ',
                    ['bold' => true, 'size' => $fontSize - 2, 'italic' => true, 'name' => $fontFamily]
                );
                $explRun->addText(
                    $question->explanation,
                    ['size' => $fontSize - 2, 'italic' => true, 'name' => $fontFamily]
                );
            }

            $section->addTextBreak(1);
        }

        // ====================
        // FOLHA DE RESPOSTAS SEPARADA
        // ====================

        if (($config['separate_answer_sheet'] ?? false) && !$withAnswers) {
            // Nova página
            $section->addPageBreak();

            $section->addText(
                'FOLHA DE RESPOSTAS',
                ['bold' => true, 'size' => $fontSize + 2, 'name' => $fontFamily],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 200]
            );

            $section->addText($exam->title, ['size' => $fontSize, 'name' => $fontFamily], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);

            $section->addTextBreak(1);
            $section->addText('Nome: ________________________________________________', ['size' => $fontSize - 1]);
            $section->addText('Turma: ____________________ Data: ___/___/________', ['size' => $fontSize - 1]);
            $section->addTextBreak(1);

            // Tabela de gabarito
            $multipleChoiceQuestions = $questions->filter(fn($q) => $q->alternatives->count() > 0);

            if ($multipleChoiceQuestions->count() > 0) {
                $tableStyle = [
                    'borderSize' => 6,
                    'borderColor' => '000000',
                    'cellMargin' => 80,
                    'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
                ];

                $table = $section->addTable($tableStyle);

                $mcIndex = 0;
                foreach ($multipleChoiceQuestions as $question) {
                    $mcIndex++;

                    $table->addRow();
                    $table->addCell(1500)->addText("$mcIndex.", ['bold' => true]);

                    // Círculos para marcar (A) (B) (C) (D) (E)
                    $cellText = $table->addCell(8000);
                    $run = $cellText->addTextRun();
                    foreach (['A', 'B', 'C', 'D', 'E'] as $letter) {
                        $run->addText("( $letter )  ", ['size' => $fontSize]);
                    }
                }
            }
        }

        // ====================
        // RODAPÉ
        // ====================

        $section->addTextBreak(1);
        $section->addLine([
            'weight' => 1,
            'width' => 450,
            'height' => 0,
            'color' => '000000'
        ]);

        if (!empty($footerConfig['custom_text'])) {
            $section->addText(
                $footerConfig['custom_text'],
                ['size' => $fontSize - 1, 'name' => $fontFamily],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
            );
        }

        if ($footerConfig['show_page_number'] ?? true) {
            $footer = $section->addFooter();
            $footer->addPreserveText(
                'Página {PAGE}',
                ['size' => 9, 'name' => $fontFamily],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
            );
        }

        // ====================
        // DOWNLOAD
        // ====================

        $filename = Str::slug($exam->title) . ($withAnswers ? '-gabarito' : '') . '.docx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save('php://output');
        exit;
    }

    /**
     * Converter margens para valor em mm
     */
    private function getMarginValue($margin)
    {
        $values = [
            'narrow' => '12.7mm',
            'normal' => '25mm',
            'wide' => '31.7mm',
        ];

        return $values[$margin] ?? $values['normal'];
    }

    /**
     * Converter margens para twips (para PhpWord)
     */
    private function getMarginValueTwips($margin)
    {
        $values = [
            'narrow' => 720,  // 12.7mm
            'normal' => 1417, // 25mm
            'wide' => 1800,   // 31.7mm
        ];

        return $values[$margin] ?? $values['normal'];
    }
}
