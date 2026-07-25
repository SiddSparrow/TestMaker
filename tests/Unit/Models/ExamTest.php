<?php

namespace Tests\Unit\Models;

use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_boot_fills_default_configs_when_created_with_empty_arrays(): void
    {
        $exam = Exam::create([
            'user_id' => $this->user->id,
            'title' => 'Prova sem configuração',
            'header_config' => [],
            'format_config' => [],
            'footer_config' => [],
            'difficulty_distribution' => [],
        ]);

        $this->assertSame('Arial', $exam->format_config['font_family']);
        $this->assertSame('A4', $exam->format_config['paper_size']);
        $this->assertFalse($exam->header_config['show_logo']);
        $this->assertSame(['easy' => 0, 'medium' => 0, 'hard' => 0], $exam->difficulty_distribution);
    }

    public function test_configs_survive_a_round_trip_through_the_database_as_arrays(): void
    {
        $exam = Exam::create([
            'user_id' => $this->user->id,
            'title' => 'Prova com configuração customizada',
            'header_config' => ['school_name' => 'Colégio Teste', 'show_date' => true],
        ]);

        $fromDatabase = Exam::find($exam->id);

        $this->assertIsArray($fromDatabase->header_config);
        $this->assertSame('Colégio Teste', $fromDatabase->header_config['school_name']);
    }

    public function test_recalculate_total_points_sums_points_respecting_overrides(): void
    {
        $exam = Exam::factory()->create(['user_id' => $this->user->id]);
        $subject = Subject::factory()->create(['user_id' => $this->user->id]);
        $type = QuestionType::factory()->create();

        $questionA = Question::factory()->create([
            'user_id' => $this->user->id,
            'subject_id' => $subject->id,
            'topic_id' => null,
            'question_type_id' => $type->id,
            'points' => 2,
        ]);
        $questionB = Question::factory()->create([
            'user_id' => $this->user->id,
            'subject_id' => $subject->id,
            'topic_id' => null,
            'question_type_id' => $type->id,
            'points' => 3,
        ]);

        $exam->questions()->attach($questionA->id, ['order' => 1, 'points_override' => 10]);
        $exam->questions()->attach($questionB->id, ['order' => 2]); // no override -> uses question.points

        $total = $exam->recalculateTotalPoints();

        $this->assertEquals(13, $total);
        $this->assertEquals(13, $exam->fresh()->total_points);
    }

    public function test_is_complete_reflects_whether_the_exam_has_questions(): void
    {
        $exam = Exam::factory()->create(['user_id' => $this->user->id]);

        $this->assertFalse($exam->isComplete());

        $subject = Subject::factory()->create(['user_id' => $this->user->id]);
        $type = QuestionType::factory()->create();
        $question = Question::factory()->create([
            'user_id' => $this->user->id,
            'subject_id' => $subject->id,
            'topic_id' => null,
            'question_type_id' => $type->id,
        ]);
        $exam->questions()->attach($question->id, ['order' => 1]);

        $this->assertTrue($exam->isComplete());
    }
}
