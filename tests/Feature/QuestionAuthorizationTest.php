<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Multi-tenant security for the question bank. QuestionController has no
 * ownership check on show/edit/update (an IDOR), and its "create"/"edit"
 * auxiliary data plus its stats block are cached under fixed, global keys
 * instead of per-user keys — so the first professor to load a page seeds
 * the cache for every other professor. These tests encode the correct,
 * isolated behaviour and are expected to fail until
 * docs/avaliacao-completude.md's security items are fixed.
 */
class QuestionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $intruder;

    private Question $question;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->owner = User::factory()->create();
        $this->intruder = User::factory()->create();

        $subject = Subject::factory()->create(['user_id' => $this->owner->id]);
        $type = QuestionType::factory()->create();

        $this->question = Question::factory()->create([
            'user_id' => $this->owner->id,
            'subject_id' => $subject->id,
            'topic_id' => null,
            'question_type_id' => $type->id,
        ]);
    }

    public function test_a_user_cannot_view_another_users_question(): void
    {
        $response = $this->actingAs($this->intruder)->get(route('questions.show', $this->question));

        $response->assertForbidden();
    }

    public function test_a_user_cannot_open_the_edit_form_for_another_users_question(): void
    {
        $response = $this->actingAs($this->intruder)->get(route('questions.edit', $this->question));

        $response->assertForbidden();
    }

    public function test_a_user_cannot_update_another_users_question(): void
    {
        $response = $this->actingAs($this->intruder)->put(route('questions.update', $this->question), [
            'subject_id' => Subject::factory()->create(['user_id' => $this->intruder->id])->id,
            'statement' => 'Enunciado adulterado por outro professor.',
            'question_type_id' => $this->question->question_type_id,
            'difficulty_level' => 'easy',
            'points' => 1,
        ]);

        $response->assertForbidden();
        $this->assertNotSame('Enunciado adulterado por outro professor.', $this->question->fresh()->statement);
    }

    public function test_a_user_cannot_copy_another_users_question(): void
    {
        $response = $this->actingAs($this->intruder)->post(route('questions.copy', $this->question));

        $response->assertForbidden();
    }

    public function test_a_user_cannot_inactivate_another_users_question(): void
    {
        $response = $this->actingAs($this->intruder)->delete(route('questions.destroy', $this->question));

        $response->assertForbidden();
        $this->assertTrue($this->question->fresh()->is_active);
    }

    public function test_question_stats_are_scoped_to_the_authenticated_user(): void
    {
        // owner already has 1 question; give the intruder 3 of their own.
        $subject = Subject::factory()->create(['user_id' => $this->intruder->id]);
        Question::factory()->count(3)->create([
            'user_id' => $this->intruder->id,
            'subject_id' => $subject->id,
            'topic_id' => null,
            'question_type_id' => $this->question->question_type_id,
        ]);

        $response = $this->actingAs($this->owner)->get(route('questions.index'));

        $response->assertInertia(fn ($page) => $page->where('stats.total', 1));
    }

    public function test_create_form_auxiliary_data_is_scoped_to_the_authenticated_user(): void
    {
        $intruderSubject = Subject::factory()->create(['user_id' => $this->intruder->id, 'name' => 'Matéria do Intruso']);
        $ownerSubject = Subject::factory()->create(['user_id' => $this->owner->id, 'name' => 'Matéria do Dono']);

        // The intruder loads the create form FIRST, seeding the (buggy) global,
        // non-user-scoped cache key ('questions_create_data').
        $this->actingAs($this->intruder)->get(route('questions.create'));

        $response = $this->actingAs($this->owner)->get(route('questions.create'));

        $response->assertInertia(fn ($page) => $page->where(
            'subjects',
            function ($subjects) use ($ownerSubject, $intruderSubject) {
                $ids = collect($subjects)->pluck('id');

                return $ids->contains($ownerSubject->id) && !$ids->contains($intruderSubject->id);
            }
        ));
    }
}
