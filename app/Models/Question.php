<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'subject_id',
        'topic_id',
        'question_type_id',
        'document_id',
        'statement',
        'explanation',
        'difficulty_level',
        'points',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function booted()
    {
        // Limpa cache quando uma questão é criada, atualizada ou deletada
        static::saved(function ($question) {
            $question->clearCache();
        });

        static::deleted(function ($question) {
            $question->clearCache();
        });
    }

    /**
     * Clear cache related to this question
     */
    public function clearCache(): void
    {
        $tags = ['questions', "question_{$this->id}"];

        Cache::tags($tags)->flush();

        // Limpa estatísticas
        Cache::forget('questions_stats');
        Cache::forget('questions_count_total');
        Cache::forget('questions_count_active');
        Cache::forget('questions_count_inactive');
        Cache::forget('questions_by_difficulty');
    }

    /**
     * Get cached paginator from array
     */
    public static function hydratePaginator(array $data): LengthAwarePaginator
    {
        return \App\Helpers\CacheHelper::arrayToPaginator(
            $data['data'],
            $data['per_page'],
            $data['current_page'],
            $data['total'],
            ['path' => $data['path'] ?? url()->current()]
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function questionType(): BelongsTo
    {
        return $this->belongsTo(QuestionType::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function alternatives(): HasMany
    {
        return $this->hasMany(QuestionAlternative::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'question_tags');
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_questions')
            ->withPivot('order', 'points_override')
            ->withTimestamps();
    }
}
