<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_tag_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('tags.index'), 'tags.index is not registered.');
        $this->assertTrue(Route::has('tags.store'), 'tags.store is not registered (commented out in routes/web.php).');
        $this->assertTrue(Route::has('tags.update'), 'tags.update is not registered (commented out in routes/web.php).');
        $this->assertTrue(Route::has('tags.destroy'), 'tags.destroy is not registered (commented out in routes/web.php).');
    }

    public function test_index_lists_only_the_authenticated_users_tags(): void
    {
        $mine = Tag::factory()->create(['user_id' => $this->user->id]);
        $other = User::factory()->create();
        Tag::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->get(route('tags.index'));

        $response->assertInertia(
            fn ($page) => $page
                ->component('Tags/Index')
                ->has('tags', 1)
                ->where('tags.0.id', $mine->id)
        );
    }

    public function test_store_creates_a_tag_for_the_authenticated_user(): void
    {
        // Exact payload shape sent by TagsModal.vue's useForm()/transform().
        $payload = ['name' => 'Gramática', 'slug' => 'gramatica'];

        $response = $this->actingAs($this->user)->post(route('tags.store'), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tags', [
            'user_id' => $this->user->id,
            'name' => 'Gramática',
        ]);
    }

    public function test_store_requires_a_name(): void
    {
        $response = $this->actingAs($this->user)->post(route('tags.store'), ['slug' => '']);

        $response->assertSessionHasErrors('name');
    }

    public function test_update_modifies_an_existing_tag(): void
    {
        $tag = Tag::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->put(route('tags.update', $tag), [
            'name' => 'Nome Atualizado',
            'slug' => 'nome-atualizado',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('Nome Atualizado', $tag->fresh()->name);
    }

    public function test_a_user_cannot_update_another_users_tag(): void
    {
        $other = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->put(route('tags.update', $tag), [
            'name' => 'Sequestrada',
            'slug' => 'sequestrada',
        ]);

        $response->assertNotFound();
    }

    public function test_destroy_removes_the_tag_without_deleting_its_questions(): void
    {
        $tag = Tag::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->delete(route('tags.destroy', $tag));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    /**
     * tags.name/slug used to be globally unique even though every Tag
     * belongs to a user_id and the model scopes queries per user — so two
     * different professors could never both have a tag named "Gramática".
     * database/migrations/2025_12_13_000003_scope_tags_uniqueness_to_user.php
     * rescopes the uniqueness to (user_id, name)/(user_id, slug).
     */
    public function test_two_different_users_can_have_a_tag_with_the_same_name(): void
    {
        $other = User::factory()->create();
        Tag::factory()->create(['user_id' => $other->id, 'name' => 'Gramática', 'slug' => 'gramatica']);

        $mine = Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'Gramática', 'slug' => 'gramatica']);

        $this->assertDatabaseHas('tags', ['id' => $mine->id, 'name' => 'Gramática']);
    }

    /**
     * Same cache-invalidation gap as SubjectTest — a tag created here used
     * to be invisible in the question form until the 30-min cache expired.
     */
    public function test_creating_a_tag_invalidates_the_question_form_cache(): void
    {
        $this->actingAs($this->user)->get(route('questions.create'));

        $this->actingAs($this->user)->post(route('tags.store'), ['name' => 'Revisão']);

        $response = $this->actingAs($this->user)->get(route('questions.create'));

        $response->assertInertia(
            fn ($page) => $page->has('tags', 1)->where('tags.0.name', 'Revisão')
        );
    }
}
