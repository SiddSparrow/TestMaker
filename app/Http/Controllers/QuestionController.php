<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\QuestionType;
use App\Models\Tag;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuestionController extends Controller
{
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
        // Filtros da requisição
        $filters = $request->only([
            'search',
            'subject_id', 
            'topic_id',
            'difficulty_level',
            'question_type_id',
            'is_active',
            'per_page'
        ]);

        // Cache key para a query com filtros
        $filtersHash = md5(serialize($filters) . $request->input('page', 1));
        $cacheKey = $this->cacheKeys['filters'] . $filtersHash;

        // Tenta obter do cache primeiro
        $questions = Cache::remember($cacheKey, $this->cacheDuration, function () use ($filters) {
            // Query base com relacionamentos
            $query = Question::with([
                'subject:id,name,color',
                'topic:id,name',
                'questionType:id,name',
                'tags:id,name',
                'alternatives:id,question_id,content,is_correct'
            ])
            ->withCount('alternatives');

            // Aplicar filtros
            $this->applyFilters($query, $filters);

            // Ordenação padrão
            $query->latest();

            // Paginação
            $perPage = $filters['per_page'] ?? 15;
            
            return $query->paginate($perPage)->withQueryString();
        });

        // Dados para filtros (com cache SEM tags)
        $subjects = Cache::remember(
            $this->cacheKeys['subjects'], 
            $this->cacheDuration, 
            fn() => Subject::select('id', 'name', 'color')->get()
        );

        $topics = Cache::remember(
            $this->cacheKeys['topics'], 
            $this->cacheDuration, 
            fn() => Topic::select('id', 'name', 'subject_id')->get()
        );

        $questionTypes = Cache::remember(
            $this->cacheKeys['question_types'], 
            $this->cacheDuration, 
            fn() => QuestionType::select('id', 'name')->get()
        );

        $tags = Cache::remember(
            $this->cacheKeys['tags'], 
            $this->cacheDuration, 
            fn() => Tag::select('id', 'name')->get()
        );

        // Estatísticas (com cache)
        $stats = $this->getCachedStats();

        // Dificuldades para filtro
        $difficultyLevels = [
            ['value' => 'easy', 'label' => 'Fácil'],
            ['value' => 'medium', 'label' => 'Média'],
            ['value' => 'hard', 'label' => 'Difícil']
        ];

        return Inertia::render('Questions/Index', [
            'questions' => $questions,
            'filters' => $filters,
            'subjects' => $subjects,
            'topics' => $topics,
            'question_types' => $questionTypes,
            'tags' => $tags,
            'difficulty_levels' => $difficultyLevels,
            'stats' => $stats
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
            $query->where('is_active', (bool)$filters['is_active']);
        }
    }

    /**
     * Get cached statistics
     */
    protected function getCachedStats(): array
    {
        return Cache::remember($this->cacheKeys['stats'], $this->cacheDuration, function () {
            return [
                'total' => Question::count(),
                'active' => Question::where('is_active', true)->count(),
                'inactive' => Question::where('is_active', false)->count(),
                'by_difficulty' => [
                    'easy' => Question::where('difficulty_level', 'easy')->count(),
                    'medium' => Question::where('difficulty_level', 'medium')->count(),
                    'hard' => Question::where('difficulty_level', 'hard')->count(),
                ]
            ];
        });
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Cache SEM tags
        $cacheKey = 'questions_create_data';
        
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
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'topic_id' => 'nullable|exists:topics,id',
            'question_type_id' => 'required|exists:question_types,id',
            'statement' => 'required|string|min:10',
            'explanation' => 'nullable|string',
            'difficulty_level' => 'required|in:easy,medium,hard',
            'points' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
            'alternatives' => 'required|array|min:2',
            'alternatives.*.content' => 'required|string',
            'alternatives.*.is_correct' => 'boolean',
            'alternatives.*.order' => 'integer',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        DB::beginTransaction();

        try {
            $question = Question::create([
                'user_id' => auth()->id(),
                'subject_id' => $validated['subject_id'],
                'topic_id' => $validated['topic_id'],
                'question_type_id' => $validated['question_type_id'],
                'statement' => $validated['statement'],
                'explanation' => $validated['explanation'],
                'difficulty_level' => $validated['difficulty_level'],
                'points' => $validated['points'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Cria as alternativas
            foreach ($validated['alternatives'] as $alternative) {
                $question->alternatives()->create([
                    'content' => $alternative['content'],
                    'is_correct' => $alternative['is_correct'] ?? false,
                    'order' => $alternative['order'] ?? 0,
                ]);
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
            return back()->with('error', 'Erro ao criar questão: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Question $question)
    {
        $cacheKey = "question_show_{$question->id}";
        
        $questionData = Cache::remember($cacheKey, $this->cacheDuration, function () use ($question) {
            return $question->load([
                'subject:id,name,color',
                'topic:id,name',
                'questionType:id,name',
                'tags:id,name',
                'alternatives:id,question_id,content,is_correct,order',
                'user:id,name,email'
            ]);
        });

        return Inertia::render('Questions/Show', [
            'question' => $questionData,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Question $question)
    {
        // Verifica permissão
        if ($question->user_id !== auth()->id() && !auth()->user()->is_admin) {
            abort(403, 'Você não tem permissão para editar esta questão.');
        }

        $cacheKey = "question_edit_{$question->id}";
        
        $data = Cache::remember($cacheKey, $this->cacheDuration, function () use ($question) {
            $question->load([
                'subject:id,name,color',
                'topic:id,name',
                'questionType:id,name',
                'tags:id,name',
                'alternatives:id,question_id,content,is_correct,order'
            ]);

            return [
                'question' => $question,
                'subjects' => Subject::select('id', 'name', 'color')->get(),
                'topics' => Topic::select('id', 'name', 'subject_id')->get(),
                'question_types' => QuestionType::select('id', 'name', 'slug')->get(),
                'tags' => Tag::select('id', 'name')->get(),
            ];
        });

        return Inertia::render('Questions/Edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Question $question)
    {
        // Verifica permissão
        if ($question->user_id !== auth()->id() && !auth()->user()->is_admin) {
            abort(403, 'Você não tem permissão para editar esta questão.');
        }

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'topic_id' => 'nullable|exists:topics,id',
            'question_type_id' => 'required|exists:question_types,id',
            'statement' => 'required|string|min:10',
            'explanation' => 'nullable|string',
            'difficulty_level' => 'required|in:easy,medium,hard',
            'points' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
            'alternatives' => 'required|array|min:2',
            'alternatives.*.id' => 'nullable|exists:question_alternatives,id',
            'alternatives.*.content' => 'required|string',
            'alternatives.*.is_correct' => 'boolean',
            'alternatives.*.order' => 'integer',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        DB::beginTransaction();

        try {
            $question->update([
                'subject_id' => $validated['subject_id'],
                'topic_id' => $validated['topic_id'],
                'question_type_id' => $validated['question_type_id'],
                'statement' => $validated['statement'],
                'explanation' => $validated['explanation'],
                'difficulty_level' => $validated['difficulty_level'],
                'points' => $validated['points'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Atualiza alternativas
            $existingAlternativeIds = $question->alternatives()->pluck('id')->toArray();
            $updatedAlternativeIds = [];

            foreach ($validated['alternatives'] as $alternativeData) {
                if (isset($alternativeData['id']) && in_array($alternativeData['id'], $existingAlternativeIds)) {
                    $question->alternatives()
                        ->where('id', $alternativeData['id'])
                        ->update([
                            'content' => $alternativeData['content'],
                            'is_correct' => $alternativeData['is_correct'] ?? false,
                            'order' => $alternativeData['order'] ?? 0,
                        ]);
                    $updatedAlternativeIds[] = $alternativeData['id'];
                } else {
                    $newAlternative = $question->alternatives()->create([
                        'content' => $alternativeData['content'],
                        'is_correct' => $alternativeData['is_correct'] ?? false,
                        'order' => $alternativeData['order'] ?? 0,
                    ]);
                    $updatedAlternativeIds[] = $newAlternative->id;
                }
            }

            // Remove alternativas não atualizadas
            $question->alternatives()
                ->whereNotIn('id', $updatedAlternativeIds)
                ->delete();

            // Atualiza tags
            $question->tags()->sync($validated['tags'] ?? []);

            DB::commit();

            // Limpa cache
            $this->clearQuestionCache($question->id);

            return redirect()->route('questions.index')
                ->with('success', 'Questão atualizada com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erro ao atualizar questão: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question)
    {
        // Verifica permissão
        if ($question->user_id !== auth()->id() && !auth()->user()->is_admin) {
            abort(403, 'Você não tem permissão para excluir esta questão.');
        }

        $questionId = $question->id;
        $question->delete();

        // Limpa cache
        $this->clearQuestionCache($questionId);

        return redirect()->route('questions.index')
            ->with('success', 'Questão excluída com sucesso!');
    }

    /**
     * Clear all cache related to questions
     */
    protected function clearQuestionCache(int $questionId = null): void
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

        // Limpa chaves específicas
        Cache::forget($this->cacheKeys['stats']);
        Cache::forget('questions_create_data');
        
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
                list($cursor, $keys) = $redis->scan($cursor, 'MATCH', $pattern);
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            } while ($cursor != 0);
        }
    }

    /**
     * Alternative: Manual cache key management
     */
    protected function getCacheKeysToClear(int $questionId = null): array
    {
        $keys = [
            $this->cacheKeys['stats'],
            'questions_create_data',
            // Adicione outras chaves fixas aqui
        ];

        // Se temos questionId, adicionamos chaves específicas
        if ($questionId) {
            $keys[] = "question_show_{$questionId}";
            $keys[] = "question_edit_{$questionId}";
        }

        return $keys;
    }
}