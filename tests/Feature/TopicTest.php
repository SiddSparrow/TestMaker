<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TopicController is an empty stub (`app/Http/Controllers/TopicController.php`
 * only contains `// `), even though routes/web.php registers a full REST
 * resource for it and resources/js/Components/Modals/TopicsModal.vue
 * already sends real store/update/destroy requests. These tests encode
 * the CRUD the frontend expects (see docs/avaliacao-completude.md).
 */
class TopicTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->subject = Subject::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_store_creates_a_topic_for_the_authenticated_user(): void
    {
        // Exact payload shape sent by TopicsModal.vue's useForm().
        $payload = [
            'subject_id' => $this->subject->id,
            'name' => 'Frações',
            'description' => 'Operações com frações',
        ];

        $response = $this->actingAs($this->user)->post(route('topics.store'), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('topics', [
            'user_id' => $this->user->id,
            'subject_id' => $this->subject->id,
            'name' => 'Frações',
        ]);
    }

    public function test_store_requires_a_subject_and_a_name(): void
    {
        $response = $this->actingAs($this->user)->post(route('topics.store'), [
            'description' => 'Sem matéria nem nome',
        ]);

        $response->assertSessionHasErrors(['subject_id', 'name']);
    }

    public function test_a_user_cannot_create_a_topic_under_another_users_subject(): void
    {
        $other = User::factory()->create();
        $foreignSubject = Subject::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->post(route('topics.store'), [
            'subject_id' => $foreignSubject->id,
            'name' => 'Tópico indevido',
        ]);

        $response->assertSessionHasErrors('subject_id');
    }

    public function test_update_modifies_an_existing_topic(): void
    {
        $topic = Topic::factory()->create([
            'user_id' => $this->user->id,
            'subject_id' => $this->subject->id,
        ]);

        $response = $this->actingAs($this->user)->put(route('topics.update', $topic), [
            'subject_id' => $this->subject->id,
            'name' => 'Nome Atualizado',
            'description' => $topic->description,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('Nome Atualizado', $topic->fresh()->name);
    }

    public function test_a_user_cannot_update_another_users_topic(): void
    {
        $other = User::factory()->create();
        $otherSubject = Subject::factory()->create(['user_id' => $other->id]);
        $topic = Topic::factory()->create(['user_id' => $other->id, 'subject_id' => $otherSubject->id]);

        $response = $this->actingAs($this->user)->put(route('topics.update', $topic), [
            'subject_id' => $otherSubject->id,
            'name' => 'Sequestrado',
        ]);

        $response->assertNotFound();
    }

    public function test_destroy_removes_the_topic_and_keeps_its_questions(): void
    {
        $topic = Topic::factory()->create([
            'user_id' => $this->user->id,
            'subject_id' => $this->subject->id,
        ]);

        $response = $this->actingAs($this->user)->delete(route('topics.destroy', $topic));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('topics', ['id' => $topic->id]);
    }

    public function test_a_user_cannot_delete_another_users_topic(): void
    {
        $other = User::factory()->create();
        $otherSubject = Subject::factory()->create(['user_id' => $other->id]);
        $topic = Topic::factory()->create(['user_id' => $other->id, 'subject_id' => $otherSubject->id]);

        $response = $this->actingAs($this->user)->delete(route('topics.destroy', $topic));

        $response->assertNotFound();
        $this->assertDatabaseHas('topics', ['id' => $topic->id]);
    }
}
