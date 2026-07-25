<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SubjectController is an empty stub (`app/Http/Controllers/SubjectController.php`
 * only contains `// `), even though routes/web.php registers a full REST
 * resource for it and resources/js/Components/Modals/SubjectsModal.vue
 * already sends real store/update/destroy requests. Every test below
 * encodes the CRUD the frontend expects and is failing until the
 * controller is implemented (see docs/avaliacao-completude.md).
 */
class SubjectTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_store_creates_a_subject_for_the_authenticated_user(): void
    {
        // Exact payload shape sent by SubjectsModal.vue's useForm().
        $payload = [
            'name' => 'Matemática',
            'description' => 'Ensino Fundamental II',
            'color' => '#3B82F6',
        ];

        $response = $this->actingAs($this->user)->post(route('subjects.store'), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('subjects', [
            'user_id' => $this->user->id,
            'name' => 'Matemática',
            'color' => '#3B82F6',
        ]);
    }

    public function test_store_requires_a_name(): void
    {
        $response = $this->actingAs($this->user)->post(route('subjects.store'), [
            'description' => 'Sem nome',
            'color' => '#3B82F6',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_update_modifies_an_existing_subject(): void
    {
        $subject = Subject::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->put(route('subjects.update', $subject), [
            'name' => 'Matemática Avançada',
            'description' => $subject->description,
            'color' => $subject->color,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('Matemática Avançada', $subject->fresh()->name);
    }

    public function test_a_user_cannot_update_another_users_subject(): void
    {
        $other = User::factory()->create();
        $subject = Subject::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->put(route('subjects.update', $subject), [
            'name' => 'Sequestrada',
            'color' => '#000000',
        ]);

        $response->assertNotFound();
    }

    public function test_destroy_removes_the_subject(): void
    {
        $subject = Subject::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->delete(route('subjects.destroy', $subject));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }

    public function test_a_user_cannot_delete_another_users_subject(): void
    {
        $other = User::factory()->create();
        $subject = Subject::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->delete(route('subjects.destroy', $subject));

        $response->assertNotFound();
        $this->assertDatabaseHas('subjects', ['id' => $subject->id]);
    }

    /**
     * SubjectsModal.vue's confirmation dialog promises that deleting a
     * subject only unlinks its questions, never destroys them.
     * questions.subject_id used to be ->onDelete('cascade'), which would
     * have silently wiped out the question bank instead. The controller
     * now checks for linked questions before deleting and returns a
     * friendly error instead of touching the database.
     */
    public function test_destroy_is_blocked_when_the_subject_still_has_questions(): void
    {
        $subject = Subject::factory()->create(['user_id' => $this->user->id]);
        $type = QuestionType::factory()->create();
        $question = Question::factory()->create([
            'user_id' => $this->user->id,
            'subject_id' => $subject->id,
            'topic_id' => null,
            'question_type_id' => $type->id,
        ]);

        $response = $this->actingAs($this->user)->delete(route('subjects.destroy', $subject));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('subjects', ['id' => $subject->id]);
        $this->assertDatabaseHas('questions', ['id' => $question->id]);
    }

    /**
     * Defense in depth: even bypassing the controller, the FK itself
     * (database/migrations/2025_12_13_000002_restrict_questions_subject_delete.php)
     * now rejects deleting a subject that still has questions, instead of
     * cascading the delete as it used to.
     */
    public function test_deleting_a_subject_with_questions_is_blocked_at_the_database_level(): void
    {
        $subject = Subject::factory()->create(['user_id' => $this->user->id]);
        $type = QuestionType::factory()->create();
        Question::factory()->create([
            'user_id' => $this->user->id,
            'subject_id' => $subject->id,
            'topic_id' => null,
            'question_type_id' => $type->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $subject->delete();
    }
}
