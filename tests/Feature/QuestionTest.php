<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Subject;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class QuestionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Subject $subject;

    private Topic $topic;

    private QuestionType $multipleChoiceType;

    private QuestionType $essayType;

    protected function setUp(): void
    {
        parent::setUp();

        // QuestionController caches auxiliary data under fixed, non-user-scoped
        // keys ('questions_create_data', 'questions_stats', ...). Flush between
        // tests so one test's cache can't leak into the next.
        Cache::flush();

        $this->user = User::factory()->create();
        $this->subject = Subject::factory()->create(['user_id' => $this->user->id]);
        $this->topic = Topic::factory()->create([
            'user_id' => $this->user->id,
            'subject_id' => $this->subject->id,
        ]);
        $this->multipleChoiceType = QuestionType::factory()->create([
            'name' => 'Múltipla Escolha',
            'slug' => 'multipla-escolha',
        ]);
        $this->essayType = QuestionType::factory()->create([
            'name' => 'Dissertativa',
            'slug' => 'dissertativa',
        ]);
    }

    public function test_index_lists_only_the_authenticated_users_questions(): void
    {
        $mine = $this->createQuestion();
        $other = User::factory()->create();
        Question::factory()->create(['user_id' => $other->id, 'question_type_id' => $this->essayType->id]);

        $response = $this->actingAs($this->user)->get(route('questions.index'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Questions/Index')
                ->has('questions.data', 1)
                ->where('questions.data.0.id', $mine->id)
        );
    }

    public function test_index_filters_by_search_term(): void
    {
        $match = $this->createQuestion(['statement' => 'Qual a capital da França?']);
        $this->createQuestion(['statement' => 'Resolva a equação do segundo grau.']);

        $response = $this->actingAs($this->user)->get(route('questions.index', ['search' => 'França']));

        $response->assertInertia(
            fn ($page) => $page
                ->has('questions.data', 1)
                ->where('questions.data.0.id', $match->id)
        );
    }

    public function test_store_creates_multiple_choice_question_with_alternatives(): void
    {
        $payload = [
            'subject_id' => $this->subject->id,
            'topic_id' => $this->topic->id,
            'question_type_id' => $this->multipleChoiceType->id,
            'statement' => 'Quanto é 2 + 2?',
            'explanation' => 'Soma simples.',
            'difficulty_level' => 'easy',
            'points' => 2,
            'is_active' => true,
            'alternatives' => [
                ['content' => '3', 'is_correct' => false, 'order' => 1],
                ['content' => '4', 'is_correct' => true, 'order' => 2],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('questions.store'), $payload);

        $response->assertRedirect(route('questions.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('questions', [
            'user_id' => $this->user->id,
            'statement' => 'Quanto é 2 + 2?',
        ]);
        $question = Question::where('statement', 'Quanto é 2 + 2?')->firstOrFail();
        $this->assertCount(2, $question->alternatives);
        $this->assertTrue($question->alternatives()->where('is_correct', true)->exists());
    }

    public function test_store_requires_at_least_one_correct_alternative_for_multiple_choice(): void
    {
        $payload = [
            'subject_id' => $this->subject->id,
            'topic_id' => $this->topic->id,
            'question_type_id' => $this->multipleChoiceType->id,
            'statement' => 'Quanto é 3 + 3?',
            'difficulty_level' => 'easy',
            'points' => 1,
            'alternatives' => [
                ['content' => '5', 'is_correct' => false, 'order' => 1],
                ['content' => '7', 'is_correct' => false, 'order' => 2],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('questions.store'), $payload);

        $response->assertSessionHasErrors('alternatives');
        $this->assertDatabaseMissing('questions', ['statement' => 'Quanto é 3 + 3?']);
    }

    public function test_store_creates_essay_question_without_alternatives(): void
    {
        $payload = [
            'subject_id' => $this->subject->id,
            'topic_id' => $this->topic->id,
            'question_type_id' => $this->essayType->id,
            'statement' => 'Discorra sobre a Revolução Industrial.',
            'difficulty_level' => 'hard',
            'points' => 5,
        ];

        $response = $this->actingAs($this->user)->post(route('questions.store'), $payload);

        $response->assertRedirect(route('questions.index'));
        $this->assertDatabaseHas('questions', ['statement' => 'Discorra sobre a Revolução Industrial.']);
    }

    public function test_update_replaces_alternatives_and_tags(): void
    {
        $question = $this->createQuestion(['question_type_id' => $this->multipleChoiceType->id]);
        $question->alternatives()->create(['content' => 'Antiga', 'is_correct' => true, 'order' => 1]);
        $tag = Tag::factory()->create(['user_id' => $this->user->id]);

        $payload = [
            'subject_id' => $this->subject->id,
            'topic_id' => $this->topic->id,
            'question_type_id' => $this->multipleChoiceType->id,
            'statement' => 'Enunciado atualizado com mais de dez caracteres.',
            'difficulty_level' => 'medium',
            'points' => 3,
            'tags' => [$tag->id],
            'alternatives' => [
                ['content' => 'Nova A', 'is_correct' => false, 'order' => 1],
                ['content' => 'Nova B', 'is_correct' => true, 'order' => 2],
            ],
        ];

        $response = $this->actingAs($this->user)->put(route('questions.update', $question), $payload);

        $response->assertRedirect(route('questions.show', $question));
        $question->refresh();
        $this->assertSame('Enunciado atualizado com mais de dez caracteres.', $question->statement);
        $this->assertCount(2, $question->alternatives);
        $this->assertTrue($question->tags->contains($tag));
    }

    public function test_destroy_inactivates_instead_of_deleting(): void
    {
        $question = $this->createQuestion();

        $response = $this->actingAs($this->user)->delete(route('questions.destroy', $question));

        $response->assertRedirect(route('questions.index'));
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'is_active' => false]);
        $this->assertNotSoftDeleted($question);
    }

    /**
     * BUG (docs/avaliacao-completude.md): QuestionController::copy() assigns
     * $newQuestion->copied_from_id, a column that does not exist in the
     * questions table, so the copy always fails with a query exception.
     */
    public function test_copy_creates_a_duplicate_question(): void
    {
        $question = $this->createQuestion(['question_type_id' => $this->multipleChoiceType->id]);
        $question->alternatives()->create(['content' => 'Única', 'is_correct' => true, 'order' => 1]);

        $response = $this->actingAs($this->user)->post(route('questions.copy', $question));

        $response->assertSessionHas('success');
        $response->assertSessionMissing('error');
        $this->assertSame(2, Question::count());
    }

    private function createQuestion(array $overrides = []): Question
    {
        return Question::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'subject_id' => $this->subject->id,
            'topic_id' => $this->topic->id,
            'question_type_id' => $this->essayType->id,
        ], $overrides));
    }
}
