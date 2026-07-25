<?php

namespace Tests\Feature\Smoke;

use App\Models\Document;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test: every registered GET route should render (200) for its owner
 * and redirect (302) for a guest.
 */
class RouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * The root route unconditionally redirects to /dashboard (it isn't
     * gated by the auth middleware), so a guest actually takes one extra
     * hop through /dashboard before the 'auth' middleware there bounces
     * them to /login. This asserts that full two-hop chain rather than a
     * direct redirect to login.
     */
    public function test_root_redirects_guest_to_login(): void
    {
        $this->get('/')->assertRedirect(route('dashboard'));
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_root_redirects_authenticated_user_to_dashboard(): void
    {
        $this->actingAs($this->user)->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_dashboard_renders_for_authenticated_user(): void
    {
        $this->actingAs($this->user)->get(route('dashboard'))->assertOk();
    }

    public function test_questions_index_renders(): void
    {
        $this->actingAs($this->user)->get(route('questions.index'))->assertOk();
    }

    public function test_questions_create_renders(): void
    {
        $this->actingAs($this->user)->get(route('questions.create'))->assertOk();
    }

    public function test_questions_show_renders(): void
    {
        $question = $this->createQuestionForUser($this->user);

        $this->actingAs($this->user)->get(route('questions.show', $question))->assertOk();
    }

    public function test_questions_edit_renders(): void
    {
        $question = $this->createQuestionForUser($this->user);

        $this->actingAs($this->user)->get(route('questions.edit', $question))->assertOk();
    }

    public function test_exams_index_renders(): void
    {
        $this->actingAs($this->user)->get(route('exams.index'))->assertOk();
    }

    public function test_exams_create_renders(): void
    {
        $this->actingAs($this->user)->get(route('exams.create'))->assertOk();
    }

    public function test_exams_show_renders(): void
    {
        $exam = Exam::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)->get(route('exams.show', $exam))->assertOk();
    }

    public function test_exams_edit_renders(): void
    {
        $exam = Exam::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)->get(route('exams.edit', $exam))->assertOk();
    }

    public function test_exams_export_pdf_downloads_a_pdf(): void
    {
        $exam = Exam::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get(route('exams.export.pdf', $exam));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_documents_index_renders(): void
    {
        $this->actingAs($this->user)->get(route('documents.index'))->assertOk();
    }

    public function test_documents_create_renders(): void
    {
        $this->actingAs($this->user)->get(route('documents.create'))->assertOk();
    }

    public function test_documents_show_renders(): void
    {
        $document = Document::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)->get(route('documents.show', $document))->assertOk();
    }

    private function createQuestionForUser(User $user): Question
    {
        $subject = Subject::factory()->create(['user_id' => $user->id]);
        $topic = Topic::factory()->create(['user_id' => $user->id, 'subject_id' => $subject->id]);
        $type = QuestionType::factory()->create();

        return Question::factory()->create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'topic_id' => $topic->id,
            'question_type_id' => $type->id,
        ]);
    }
}
