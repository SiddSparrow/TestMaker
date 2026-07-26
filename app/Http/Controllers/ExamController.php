<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Subject;
use App\Models\Topic;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ExamController extends Controller
{
    use AuthorizesRequests;

    /**
     * Colunas pelas quais a listagem pode ser ordenada — mesma lógica de
     * whitelist usada em QuestionController@index.
     */
    protected $sortableColumns = ['title', 'exam_date', 'total_points', 'created_at'];

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'sort', 'direction', 'per_page']);

        // Sem eager-load de `questions` aqui: a listagem não precisa das
        // questões de cada prova, só da contagem — carregá-las (com suas
        // alternativas etc.) para todas as provas de uma vez é o que fazia
        // a tela travar a partir de ~50 provas.
        $query = Exam::with(['subject:id,name,color', 'user:id,name'])
            ->withCount('questions')
            ->byUser(auth()->id());

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        $sort = $filters['sort'] ?? null;
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if ($sort && in_array($sort, $this->sortableColumns, true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->latest();
        }

        $perPage = $filters['per_page'] ?? 15;

        // Estatísticas leves (contagem e próxima data) sem carregar a lista
        // inteira — os cards da tela precisavam disso antes de a listagem
        // vir do array completo de provas.
        $stats = [
            'total' => Exam::byUser(auth()->id())->count(),
            'next_exam_date' => Exam::byUser(auth()->id())
                ->whereNotNull('exam_date')
                ->whereDate('exam_date', '>=', now()->toDateString())
                ->orderBy('exam_date')
                ->value('exam_date'),
        ];

        return Inertia::render('Exams/Index', [
            'exams' => $query->paginate($perPage)->withQueryString(),
            'filters' => $filters,
            'stats' => $stats,
            'questionTypes' => QuestionType::all(),
        ]);
    }

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
        $validated = $request->validate($this->examRules());

        $exam = Exam::create([
            'user_id' => auth()->id(),
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
            'total_points' => 0,
        ]);

        $this->syncQuestions($exam, $validated['questions']);
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
        $this->authorize('view', $exam);

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
                    'tags:id,name,slug',
                ])->orderBy('exam_questions.order');
            },
        ]);

        return Inertia::render('Exams/Show', [
            'exam' => $exam,
            'questionTypes' => QuestionType::all(),
        ]);
    }

    /**
     * Endpoint JSON puro (não Inertia) usado por Exams/Index.vue para
     * carregar as questões de uma prova sob demanda, ao abrir o preview —
     * a listagem não as traz mais de cada prova (ver index()).
     */
    public function questions(Exam $exam)
    {
        $this->authorize('view', $exam);

        $exam->load([
            'questions' => function ($query) {
                $query->with(['subject', 'questionType', 'alternatives', 'topic'])
                    ->orderBy('exam_questions.order');
            },
        ]);

        return response()->json([
            'questions' => $exam->questions,
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
            },
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

        $validated = $request->validate($this->examRules());

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

        $this->syncQuestions($exam, $validated['questions']);
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

        $newExam = $exam->replicate();
        $newExam->title = $exam->title . ' (Cópia)';
        $newExam->is_published = false;
        $newExam->published_at = null;
        $newExam->save();

        foreach ($exam->questions as $question) {
            $newExam->questions()->attach($question->id, [
                'order' => $question->pivot->order,
                'points_override' => $question->pivot->points_override,
            ]);
        }

        $newExam->recalculateTotalPoints();

        return redirect()
            ->route('exams.edit', $newExam)
            ->with('success', 'Prova duplicada com sucesso! Edite conforme necessário.');
    }

    public function exportPdf(Exam $exam, Request $request)
    {
        $this->authorize('view', $exam);

        $exam->load([
            'subject',
            'questions' => function ($query) use ($exam) {
                $query->orderBy('exam_questions.order');

                if (($exam->format_config['shuffle_questions'] ?? false) == true) {
                    $query->inRandomOrder();
                }
            },
            'questions.alternatives' => function ($query) {
                $query->orderBy('order');
            },
        ]);

        $withAnswers = $request->boolean('with_answers', false);
        $questions = $exam->questions;

        if (($exam->format_config['shuffle_alternatives'] ?? false) == true) {
            $questions = $questions->map(function ($question) {
                $question->alternatives = $question->alternatives->shuffle();

                return $question;
            });
        }

        $pdf = Pdf::loadView('exams.pdf', [
            'exam' => $exam,
            'questions' => $questions,
            'showAnswers' => $withAnswers,
        ])
            ->setPaper(
                $exam->format_config['paper_size'] ?? 'A4',
                $exam->format_config['orientation'] ?? 'portrait'
            );

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
            },
        ]);

        $withAnswers = $request->boolean('with_answers', false);

        $phpWord = new \PhpOffice\PhpWord\PhpWord;
        $phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language('pt-BR'));

        $config = $exam->format_config ?? [];
        $headerConfig = $exam->header_config ?? [];
        $footerConfig = $exam->footer_config ?? [];

        $sectionStyle = [
            'marginTop' => $this->getMarginValueTwips($config['margins'] ?? 'normal'),
            'marginBottom' => $this->getMarginValueTwips($config['margins'] ?? 'normal'),
            'marginLeft' => $this->getMarginValueTwips($config['margins'] ?? 'normal'),
            'marginRight' => $this->getMarginValueTwips($config['margins'] ?? 'normal'),
        ];

        if (($config['orientation'] ?? 'portrait') === 'landscape') {
            $sectionStyle['orientation'] = 'landscape';
        }

        // Section::addColumnBreak() não existe no PhpWord instalado — layout
        // em colunas é uma propriedade de estilo da seção, não uma chamada
        // após criá-la. Sem essa correção, exportar em DOCX com
        // format_config.columns=2 lançava um erro fatal.
        if (($config['columns'] ?? 1) === 2) {
            $sectionStyle['colsNum'] = 2;
            $sectionStyle['colsSpace'] = 720;
        }

        $section = $phpWord->addSection($sectionStyle);

        $fontSize = (int) str_replace('pt', '', $config['font_size'] ?? '12pt');
        $fontFamily = $config['font_family'] ?? 'Arial';
        $lineSpacing = (float) ($config['line_spacing'] ?? '1.5') * 240;

        // Cabeçalho
        if ($headerConfig['show_logo'] ?? false) {
            $section->addText(
                '[LOGO DA ESCOLA]',
                ['size' => 10, 'color' => '666666', 'name' => $fontFamily],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 100]
            );
        }

        if (!empty($headerConfig['school_name'])) {
            $section->addText(
                $headerConfig['school_name'],
                ['bold' => true, 'size' => $fontSize + 4, 'name' => $fontFamily],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]
            );
        }

        $section->addText(
            $exam->title,
            ['bold' => true, 'size' => $fontSize + 2, 'name' => $fontFamily],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]
        );

        if ($exam->description) {
            $section->addText(
                $exam->description,
                ['size' => $fontSize - 1, 'name' => $fontFamily],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]
            );
        }

        if (($headerConfig['show_date'] ?? true) && $exam->exam_date) {
            $section->addText(
                'Data: ' . $exam->exam_date->format('d/m/Y'),
                ['size' => $fontSize - 1, 'name' => $fontFamily],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]
            );
        }

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

        $section->addTextBreak(1);
        $section->addText(
            'Valor Total: ' . $exam->total_points . ' pontos',
            ['bold' => true, 'size' => $fontSize, 'name' => $fontFamily],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        $section->addLine(['weight' => 1, 'width' => 450, 'height' => 0, 'color' => '000000']);
        $section->addTextBreak(1);

        // Questões
        $questions = $exam->questions;

        if ($config['shuffle_alternatives'] ?? false) {
            $questions = $questions->map(function ($question) {
                $question->alternatives = $question->alternatives->shuffle();

                return $question;
            });
        }

        foreach ($questions as $index => $question) {
            $points = $question->pivot->points_override ?? $question->points;

            $paragraphStyle = [
                'spaceAfter' => 100,
                'lineHeight' => $lineSpacing,
            ];

            if ($config['justify_text'] ?? false) {
                $paragraphStyle['alignment'] = \PhpOffice\PhpWord\SimpleType\Jc::BOTH;
            }

            $questionRun = $section->addTextRun($paragraphStyle);
            $questionRun->addText(
                ($index + 1) . '. ',
                ['bold' => true, 'size' => $fontSize, 'name' => $fontFamily]
            );
            $questionRun->addText(
                $question->statement . ' ',
                ['size' => $fontSize, 'name' => $fontFamily]
            );

            if ($config['show_question_points'] ?? true) {
                $questionRun->addText(
                    '(' . $points . ' ' . ($points == 1 ? 'ponto' : 'pontos') . ')',
                    ['size' => $fontSize - 2, 'italic' => true, 'name' => $fontFamily]
                );
            }

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
                            'name' => $fontFamily,
                        ]);
                    }
                }
            } elseif ($config['show_answer_space'] ?? true) {
                for ($i = 0; $i < 4; $i++) {
                    $section->addText(
                        '_____________________________________________',
                        ['size' => $fontSize - 1, 'name' => $fontFamily],
                        ['indentation' => ['left' => 360]]
                    );
                }
            }

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

        // Folha de respostas separada
        if (($config['separate_answer_sheet'] ?? false) && !$withAnswers) {
            $section->addPageBreak();

            $section->addText(
                'FOLHA DE RESPOSTAS',
                ['bold' => true, 'size' => $fontSize + 2, 'name' => $fontFamily],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 200]
            );

            $section->addText(
                $exam->title,
                ['size' => $fontSize, 'name' => $fontFamily],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
            );

            $section->addTextBreak(1);
            $section->addText('Nome: ________________________________________________', ['size' => $fontSize - 1]);
            $section->addText('Turma: ____________________ Data: ___/___/________', ['size' => $fontSize - 1]);
            $section->addTextBreak(1);

            $multipleChoiceQuestions = $questions->filter(fn ($q) => $q->alternatives->count() > 0);

            if ($multipleChoiceQuestions->count() > 0) {
                $table = $section->addTable([
                    'borderSize' => 6,
                    'borderColor' => '000000',
                    'cellMargin' => 80,
                    'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
                ]);

                $mcIndex = 0;
                foreach ($multipleChoiceQuestions as $question) {
                    $mcIndex++;

                    $table->addRow();
                    $table->addCell(1500)->addText("$mcIndex.", ['bold' => true]);

                    $cellText = $table->addCell(8000);
                    $run = $cellText->addTextRun();
                    foreach (['A', 'B', 'C', 'D', 'E'] as $letter) {
                        $run->addText("( $letter )  ", ['size' => $fontSize]);
                    }
                }
            }
        }

        // Rodapé
        $section->addTextBreak(1);
        $section->addLine(['weight' => 1, 'width' => 450, 'height' => 0, 'color' => '000000']);

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

        $filename = Str::slug($exam->title) . ($withAnswers ? '-gabarito' : '') . '.docx';

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');

        ob_start();
        $writer->save('php://output');
        $document = ob_get_clean();

        return new Response($document, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Regras de validação compartilhadas por store() e update() — as
     * questões precisam pertencer ao usuário autenticado, não apenas
     * existir na tabela (senão uma prova pode ser montada com o banco de
     * questões privado de outro professor).
     */
    private function examRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'exam_date' => 'nullable|date',
            'main_subject_id' => 'required|exists:subjects,id',

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

            'questions' => 'required|array|min:1',
            'questions.*.question_id' => [
                'required',
                Rule::exists('questions', 'id')->where('user_id', auth()->id()),
            ],
            'questions.*.order' => 'required|integer|min:1',
            'questions.*.points_override' => 'nullable|numeric|min:0',
        ];
    }

    private function syncQuestions(Exam $exam, array $questions): void
    {
        $questionsToSync = [];

        foreach ($questions as $questionData) {
            $questionsToSync[$questionData['question_id']] = [
                'order' => $questionData['order'],
                'points_override' => $questionData['points_override'] ?? null,
            ];
        }

        $exam->questions()->sync($questionsToSync);
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
