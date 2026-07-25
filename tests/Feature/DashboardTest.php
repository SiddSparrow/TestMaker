<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_the_correct_component_and_stats_for_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $subject = Subject::factory()->create(['user_id' => $user->id]);
        $type = QuestionType::factory()->create();
        Question::factory()->count(3)->create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'topic_id' => null,
            'question_type_id' => $type->id,
        ]);
        Exam::factory()->count(2)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Dashboard')
                ->where('stats.total_questions', 3)
                ->where('stats.total_exams', 2)
                ->where('stats.total_subjects', 1)
        );
    }

    /**
     * Every stat in DashboardController::index() is already filtered by
     * `where('user_id', $userId)`, so — unlike QuestionController — the
     * dashboard is correctly isolated per user. This test guards that.
     */
    public function test_dashboard_stats_do_not_include_another_users_data(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $otherSubject = Subject::factory()->create(['user_id' => $other->id]);
        $type = QuestionType::factory()->create();
        Question::factory()->count(5)->create([
            'user_id' => $other->id,
            'subject_id' => $otherSubject->id,
            'topic_id' => null,
            'question_type_id' => $type->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page->where('stats.total_questions', 0));
    }
}
