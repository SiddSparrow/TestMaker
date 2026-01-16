<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'main_subject_id',
        'header_config',
        'format_config',
        'footer_config',
        'exam_date',
        'total_points',
        'difficulty_distribution',
        'topic_distribution',
        'target_total_points',
        'target_question_count',
        'is_published',
        'published_at',
    ];

    /**
     * Casts automáticos para JSON
     */
    protected $casts = [
        'exam_date' => 'date',
        'header_config' => 'array',
        'format_config' => 'array',
        'footer_config' => 'array',
        'difficulty_distribution' => 'array',
        'topic_distribution' => 'array',
        'total_points' => 'decimal:2',
        'target_total_points' => 'decimal:2',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Boot method para definir valores padrão
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($exam) {
            if (empty($exam->header_config)) {
                $exam->header_config = [
                    'school_name' => '',
                    'show_date' => true,
                    'show_student_info' => true,
                    'show_logo' => false,
                ];
            }

            if (empty($exam->format_config)) {
                $exam->format_config = [
                    'font_size' => '12pt',
                    'font_family' => 'Arial',
                    'line_spacing' => '1.5',
                    'justify_text' => false,
                    'columns' => 1,
                    'margins' => 'normal',
                    'orientation' => 'portrait',
                    'paper_size' => 'A4',
                    'show_question_points' => true,
                    'shuffle_questions' => false,
                    'shuffle_alternatives' => false,
                    'show_answer_space' => true,
                    'separate_answer_sheet' => false,
                ];
            }

            if (empty($exam->footer_config)) {
                $exam->footer_config = [
                    'custom_text' => 'Boa prova!',
                    'show_page_number' => true,
                ];
            }

            if (empty($exam->difficulty_distribution)) {
                $exam->difficulty_distribution = [
                    'easy' => 0,
                    'medium' => 0,
                    'hard' => 0,
                ];
            }

            if (!isset($exam->topic_distribution)) {
                $exam->topic_distribution = [];
            }
        });
    }

    /**
     * ========================================
     * RELACIONAMENTOS
     * ========================================
     */

    /**
     * Relacionamento com User (quem criou a prova)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com Subject (matéria principal da prova)
     * ⚠️ IMPORTANTE: O campo no banco é 'main_subject_id'
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'main_subject_id');
    }

    /**
     * Relacionamento com Questions (questões da prova)
     * Tabela pivot: exam_questions
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_questions')
            ->withPivot(['order', 'points_override'])
            ->withTimestamps()
            ->orderBy('exam_questions.order');
    }

    /**
     * ========================================
     * SCOPES
     * ========================================
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * ========================================
     * ACCESSORS
     * ========================================
     */
    public function getQuestionCountAttribute()
    {
        return $this->questions()->count();
    }

    public function getFormattedExamDateAttribute(): ?string
    {
        return $this->exam_date?->format('d/m/Y');
    }

    /**
     * ========================================
     * MÉTODOS AUXILIARES
     * ========================================
     */

    /**
     * Recalcula o total de pontos baseado nas questões
     */
    public function recalculateTotalPoints(): float
    {
        $total = $this->questions->sum(function ($question) {
            return $question->pivot->points_override ?? $question->points;
        });

        $this->update(['total_points' => $total]);

        return $total;
    }

    /**
     * Verifica se a prova está completa (tem questões)
     */
    public function isComplete(): bool
    {
        return $this->questions()->count() > 0;
    }

    /**
     * ========================================
     * MUTATORS (para PostgreSQL)
     * ========================================
     */
    public function setHeaderConfigAttribute($value)
    {
        $this->attributes['header_config'] = is_array($value) ? json_encode($value) : $value;
    }

    public function setFormatConfigAttribute($value)
    {
        $this->attributes['format_config'] = is_array($value) ? json_encode($value) : $value;
    }

    public function setFooterConfigAttribute($value)
    {
        $this->attributes['footer_config'] = is_array($value) ? json_encode($value) : $value;
    }

    public function setDifficultyDistributionAttribute($value)
    {
        $this->attributes['difficulty_distribution'] = is_array($value) ? json_encode($value) : $value;
    }

    public function setTopicDistributionAttribute($value)
    {
        $this->attributes['topic_distribution'] = is_array($value) ? json_encode($value) : $value;
    }
}
