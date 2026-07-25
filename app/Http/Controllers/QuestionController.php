<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Subject;
use App\Models\Tag;
use App\Models\Topic;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class QuestionController extends Controller
{
    use AuthorizesRequests;

    /**
     * Cache duration in seconds
     */
    protected $cacheDuration = 1800; // 30 minutos

    /**
     * Cache keys
     */
    protected $cacheKeys = [
        'subjects' => 'subjects_all',
        'topics' => 'topics_all',
        'question_types' => 'question_types_all',
        'tags' => 'tags_all',
        'stats' => 'questions_stats',
        'filters' => 'questions_filters_',
        'questions_list' => 'questions_list_',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        // Filtros da requisição
        $filters = $request->only([
            'search',
            'subject_id',
            'topic_id',
            'difficulty_level',
            'question_type_id',
            'is_active',
            'per_page',
        ]);

        // Cache key para a query com filtros + userId (importante!)
        $filtersHash = md5(serialize($filters) . $userId . $request->input('page', 1));
        $cacheKey = $this->cacheKeys['filters'] . $filtersHash;

        // Tenta obter do cache primeiro
        $questions = Cache::remember($cacheKey, $this->cacheDuration, function () use ($filters, $userId) {
            // Query base com relacionamentos
            $query = Question::with([
                'subject:id,name,color',
                'topic:id,name',
                'questionType:id,name',
                'tags:id,name',
                'alternatives:id,question_id,content,is_correct',
            ])
                ->where('user_id', $userId)  // Filtrar por usuário
                ->orderBy('is_active', 'desc')
                ->withCount('alternatives');

            // Aplicar filtros
            $this->applyFilters($query, $filters);

            // Ordenação padrão
            $query->latest();

            // Paginação
            $perPage = $filters['per_page'] ?? 15;

            return $query->paginate($perPage)->withQueryString();
        });

        // Dados para filtros (com cache)
        $subjects = Cache::remember(
            $this->cacheKeys['subjects'] . $userId,  // Cache por usuário
            $this->cacheDuration,
            fn () => Subject::select('id', 'name', 'color')
                ->where('user_id', $userId)
                ->get()
        );

        $topics = Cache::remember(
            $this->cacheKeys['topics'] . $userId,  // Cache por usuário
            $this->cacheDuration,
            fn () => Topic::select('id', 'name', 'subject_id')
                ->where('user_id', $userId)
                ->get()
        );

        $questionTypes = Cache::remember(
            $this->cacheKeys['question_types'],
            $this->cacheDuration,
            fn () => QuestionType::select('id', 'name')->get()
        );

        $tags = Cache::remember(
            $this->cacheKeys['tags'] . $userId,  // Cache por usuário
            $this->cacheDuration,
            fn () => Tag::select('id', 'name')
                ->where('user_id', $userId)
                ->get()
        );

        // Estatísticas (com cache)
        $stats = $this->getCachedStats();

        // Dificuldades para filtro
        $difficultyLevels = [
            ['value' => 'easy', 'label' => 'Fácil'],
            ['value' => 'medium', 'label' => 'Média'],
            ['value' => 'hard', 'label' => 'Difícil'],
        ];

        return Inertia::render('Questions/Index', [
            'questions' => $questions,
            'filters' => $filters,
            'subjects' => $subjects,
            'topics' => $topics,
            'question_types' => $questionTypes,
            'tags' => $tags,
            'difficulty_levels' => $difficultyLevels,
            'stats' => $stats,
        ]);
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('statement', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('explanation', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        if (!empty($filters['topic_id'])) {
            $query->where('topic_id', $filters['topic_id']);
        }

        if (!empty($filters['difficulty_level'])) {
            $query->where('difficulty_level', $filters['difficulty_level']);
        }

        if (!empty($filters['question_type_id'])) {
            $query->where('question_type_id', $filters['question_type_id']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }
    }

    /*
     * Método para cópia de questões
    */
    public function copy(Request $request, Question $question)
    {
        $this->authorize('view', $question);

        DB::beginTransaction();

        try {
            // 1. Copia os dados principais da questão
            $newQuestion = $question->replicate();
            $newQuestion->user_id = auth()->id();
            $newQuestion->is_active = true;
            $newQuestion->copied_from_id = $question->id; // Salva referência
            $newQuestion->statement = $this->addCopyIdentifier($question->statement);
            $newQuestion->save();

            // 2. Copia as alternativas
            foreach ($question->alternatives as $alternative) {
                $newAlternative = $alternative->replicate();
                $newAlternative->question_id = $newQuestion->id;
                $newAlternative->save();
            }

            // 3. Copia as tags (relacionamento many-to-many)
            $newQuestion->tags()->sync($question->tags->pluck('id'));

            // 4. Opcional: copia anexos/imagens se tiver
            // if ($question->attachments) { ... }

            DB::commit();

            $this->clearQuestionCache();

            // Retorna para a edição da nova questão
            return redirect()->route('questions.edit', $newQuestion->id)
                ->with('success', 'Cópia criada com sucesso! Edite a nova questão.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Erro ao criar cópia: ' . $e->getMessage());
        }
    }

    /**
     * Get cached statistics — escopadas por usuário para não vazar
     * contagens entre professores diferentes.
     */
    protected function getCachedStats(): array
    {
        $userId = Auth::id();

        return Cache::remember($this->cacheKeys['stats'] . '_' . $userId, $this->cacheDuration, function () use ($userId) {
            return [
                'total' => Question::where('user_id', $userId)->count(),
                'active' => Question::where('user_id', $userId)->where('is_active', true)->count(),
                'inactive' => Question::where('user_id', $userId)->where('is_active', false)->count(),
                'by_difficulty' => [
                    'easy' => Question::where('user_id', $userId)->where('difficulty_level', 'easy')->count(),
                    'medium' => Question::where('user_id', $userId)->where('difficulty_level', 'medium')->count(),
                    'hard' => Question::where('user_id', $userId)->where('difficulty_level', 'hard')->count(),
                ],
            ];
        });
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Chave por usuário: Subject/Topic/Tag já são filtrados pelo global
        // scope de auth()->id(), mas sem o user_id na chave o cache
        // devolveria os dados do primeiro professor a abrir esta tela para
        // todos os outros.
        $cacheKey = 'questions_create_data_' . Auth::id();

        $data = Cache::remember($cacheKey, $this->cacheDuration, function () {
            return [
                'subjects' => Subject::select('id', 'name', 'color')->get(),
                'topics' => Topic::select('id', 'name', 'subject_id')->get(),
                'question_types' => QuestionType::select('id', 'name', 'slug')->get(),
                'tags' => Tag::select('id', 'name')->get(),
            ];
        });

        return Inertia::render('Questions/Create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Busca o tipo de questão para validação condicional
        $questionType = QuestionType::find($request->question_type_id);
        // Define se o tipo requer alternativas
        $requiresAlternatives = $questionType &&
            in_array($questionType->slug, ['multipla-escolha', 'verdadeiro-falso', 'multipla-resposta']);
        // Se for uma cópia via formulário (do botão na edição)
        if ($request->has('copy_from_id')) {
            $originalQuestion = Question::findOrFail($request->copy_from_id);
            $this->authorize('view', $originalQuestion);

            $validated = $request->validate([
                // suas regras de validação normais
                'subject_id' => 'required|exists:subjects,id',
                'topic_id' => 'nullable|exists:topics,id',
                'question_type_id' => 'required|exists:question_types,id',
                'statement' => 'required|string|min:10',
                'explanation' => 'nullable|string',
                'difficulty_level' => 'required|in:easy,medium,hard',
                'points' => 'required|integer|min:1|max:10',
                'is_active' => 'boolean',
                'tags' => 'nullable|array',
                'tags.*' => 'exists:tags,id',

            ]);

            // Cria cópia similar ao método copy()
            return $this->createCopyFromRequest($originalQuestion, $validated);
        }
        // Validação condicional
        $rules = [
            'subject_id' => 'required|exists:subjects,id',
            'topic_id' => 'nullable|exists:topics,id',
            'question_type_id' => 'required|exists:question_types,id',
            'statement' => 'required|string|min:10',
            'explanation' => 'nullable|string',
            'difficulty_level' => 'required|in:easy,medium,hard',
            'points' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ];

        // Adiciona validação de alternativas apenas se necessário
        if ($requiresAlternatives) {
            $rules['alternatives'] = 'required|array|min:2';
            $rules['alternatives.*.content'] = 'required|string';
            $rules['alternatives.*.is_correct'] = 'boolean';
            $rules['alternatives.*.order'] = 'integer';

            // Validação customizada: pelo menos uma alternativa correta
            $rules['alternatives'] = [
                'required',
                'array',
                'min:2',
                function ($attribute, $value, $fail) {
                    $hasCorrect = collect($value)->contains('is_correct', true);
                    if (!$hasCorrect) {
                        $fail('Pelo menos uma alternativa deve estar marcada como correta.');
                    }
                },
            ];
        } else {
            // Para dissertativa, alternativas são opcionais/ignoradas
            $rules['alternatives'] = 'nullable|array';
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();

        try {
            $question = Question::create([
                'user_id' => auth()->id(),
                'subject_id' => $validated['subject_id'],
                'topic_id' => $validated['topic_id'],
                'question_type_id' => $validated['question_type_id'],
                'statement' => $validated['statement'],
                'explanation' => $validated['explanation'] ?? null,
                'difficulty_level' => $validated['difficulty_level'],
                'points' => $validated['points'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Cria as alternativas apenas se o tipo exigir
            if ($requiresAlternatives && !empty($validated['alternatives'])) {
                foreach ($validated['alternatives'] as $alternative) {
                    $question->alternatives()->create([
                        'content' => $alternative['content'],
                        'is_correct' => $alternative['is_correct'] ?? false,
                        'order' => $alternative['order'] ?? 0,
                    ]);
                }
            }

            // Adiciona tags
            if (!empty($validated['tags'])) {
                $question->tags()->sync($validated['tags']);
            }

            DB::commit();

            // Limpa cache relacionado a questões
            $this->clearQuestionCache();

            return redirect()->route('questions.index')
                ->with('success', 'Questão criada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erro ao criar questão', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Erro ao criar questão: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Question $question)
    {
        $this->authorize('view', $question);

        // Carrega relacionamentos necessários
        $question->load([
            'subject:id,name,color',
            'topic:id,name',
            'questionType:id,name,slug',
            'user:id,name',
            'alternatives' => function ($query) {
                $query->orderBy('order');
            },
            'tags:id,name',
        ]);

        return Inertia::render('Questions/Show', [
            'question' => $question,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Question $question)
    {
        $this->authorize('update', $question);

        // Cache dos dados auxiliares, por usuário (ver nota em create())
        $cacheKey = 'questions_edit_data_' . Auth::id();

        $data = Cache::remember($cacheKey, $this->cacheDuration, function () {
            return [
                'subjects' => Subject::select('id', 'name', 'color')->get(),
                'topics' => Topic::select('id', 'name', 'subject_id')->get(),
                'question_types' => QuestionType::select('id', 'name', 'slug')->get(),
                'tags' => Tag::select('id', 'name')->get(),
            ];
        });

        // Carrega a questão com seus relacionamentos
        $question->load([
            'alternatives' => function ($query) {
                $query->orderBy('order');
            },
            'tags:id,name',
        ]);

        return Inertia::render('Questions/Edit', array_merge($data, [
            'question' => $question,
        ]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Question $question)
    {
        $this->authorize('update', $question);

        // Busca o tipo de questão para validação condicional
        $questionType = QuestionType::find($request->question_type_id);

        // Define se o tipo requer alternativas
        $requiresAlternatives = $questionType &&
            in_array($questionType->slug, ['multipla-escolha', 'verdadeiro-falso', 'multipla-resposta']);

        // Validação condicional
        $rules = [
            'subject_id' => 'required|exists:subjects,id',
            'topic_id' => 'nullable|exists:topics,id',
            'question_type_id' => 'required|exists:question_types,id',
            'statement' => 'required|string|min:10',
            'explanation' => 'nullable|string',
            'difficulty_level' => 'required|in:easy,medium,hard',
            'points' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ];

        // Adiciona validação de alternativas apenas se necessário
        if ($requiresAlternatives) {
            $rules['alternatives'] = [
                'required',
                'array',
                'min:2',
                function ($attribute, $value, $fail) {
                    $hasCorrect = collect($value)->contains('is_correct', true);
                    if (!$hasCorrect) {
                        $fail('Pelo menos uma alternativa deve estar marcada como correta.');
                    }
                },
            ];
            $rules['alternatives.*.content'] = 'required|string';
            $rules['alternatives.*.is_correct'] = 'boolean';
            $rules['alternatives.*.order'] = 'integer';
        } else {
            $rules['alternatives'] = 'nullable|array';
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();

        try {
            // Atualiza a questão
            $question->update([
                'subject_id' => $validated['subject_id'],
                'topic_id' => $validated['topic_id'],
                'question_type_id' => $validated['question_type_id'],
                'statement' => $validated['statement'],
                'explanation' => $validated['explanation'] ?? null,
                'difficulty_level' => $validated['difficulty_level'],
                'points' => $validated['points'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Atualiza alternativas
            if ($requiresAlternatives && !empty($validated['alternatives'])) {
                // Remove alternativas antigas
                $question->alternatives()->delete();

                // Cria novas alternativas
                foreach ($validated['alternatives'] as $alternative) {
                    $question->alternatives()->create([
                        'content' => $alternative['content'],
                        'is_correct' => $alternative['is_correct'] ?? false,
                        'order' => $alternative['order'] ?? 0,
                    ]);
                }
            } else {
                // Se não requer alternativas, remove todas
                $question->alternatives()->delete();
            }

            // Atualiza tags
            if (isset($validated['tags'])) {
                $question->tags()->sync($validated['tags']);
            } else {
                $question->tags()->detach();
            }

            DB::commit();

            // Limpa cache relacionado (passa o ID da questão)
            $this->clearQuestionCache($question->id);

            return redirect()->route('questions.show', $question->id)
                ->with('success', 'Questão atualizada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erro ao atualizar questão', [
                'question_id' => $question->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Erro ao atualizar questão: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question)
    {
        $this->authorize('delete', $question);

        $questionId = $question->id;
        // $question->delete();
        $question->is_active = false;
        $question->save();

        // Limpa cache
        $this->clearQuestionCache($questionId);

        return redirect()->route('questions.index')
            ->with('success', 'Questão inativada com sucesso!');
    }

    /**
     * Clear all cache related to questions
     */
    protected function clearQuestionCache(?int $questionId = null): void
    {
        // Padrão de chaves para limpar
        $patterns = [
            $this->cacheKeys['filters'] . '*',
            $this->cacheKeys['questions_list'] . '*',
        ];

        if ($questionId) {
            // Limpa cache específico da questão
            Cache::forget("question_show_{$questionId}");
            Cache::forget("question_edit_{$questionId}");

            // Adiciona ao padrão de filtros
            $patterns[] = "*question_{$questionId}*";
        }

        // Limpa chaves específicas (escopadas por usuário — ver create()/edit()/getCachedStats())
        Cache::forget($this->cacheKeys['stats'] . '_' . Auth::id());
        Cache::forget('questions_create_data_' . Auth::id());
        Cache::forget('questions_edit_data_' . Auth::id());

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                $this->clearCacheByPattern($pattern);
            } else {
                Cache::forget($pattern);
            }
        }
    }

    /**
     * Clear cache by pattern (usando Redis scan)
     */
    protected function clearCacheByPattern(string $pattern): void
    {
        // Se estiver usando Redis, podemos usar SCAN
        if (config('cache.default') === 'redis') {
            $redis = Cache::getStore()->getRedis();

            // Remove o prefixo do Laravel do pattern
            $prefix = config('cache.prefix');
            $pattern = $prefix . ':' . str_replace('*', '*', $pattern);

            // Usa SCAN para encontrar e deletar chaves
            $cursor = 0;
            do {
                [$cursor, $keys] = $redis->scan($cursor, 'MATCH', $pattern);
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            } while ($cursor != 0);
        }
    }

    private function createCopyFromRequest(Question $original, array $data)
    {
        DB::beginTransaction();

        try {
            $newQuestion = $original->replicate();
            $newQuestion->fill($data);
            $newQuestion->user_id = auth()->id();
            $newQuestion->copied_from_id = $original->id;
            $newQuestion->is_active = $data['is_active'] ?? true;
            $newQuestion->save();

            // Copia alternativas se existirem no request; senão, copia as da original
            // (caso de uma questão dissertativa que não envia alternativas).
            if (isset($data['alternatives']) && is_array($data['alternatives'])) {
                foreach ($data['alternatives'] as $altData) {
                    $newQuestion->alternatives()->create($altData);
                }
            } else {
                foreach ($original->alternatives as $alternative) {
                    $newAlternative = $alternative->replicate();
                    $newAlternative->question_id = $newQuestion->id;
                    $newAlternative->save();
                }
            }

            // Copia tags
            if (isset($data['tags']) && is_array($data['tags'])) {
                $newQuestion->tags()->sync($data['tags']);
            } else {
                $newQuestion->tags()->sync($original->tags->pluck('id'));
            }

            DB::commit();

            $this->clearQuestionCache();

            return redirect()->route('questions.edit', $newQuestion->id)
                ->with('success', 'Cópia criada com sucesso! Agora basta editar a nova questão.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Erro ao criar cópia: ' . $e->getMessage());
        }
    }

    private function addCopyIdentifier(string $statement): string
    {
        // Adiciona um indicador que é cópia (opcional)
        return '(Cópia) ' . $statement;
    }
}
