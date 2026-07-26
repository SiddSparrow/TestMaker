<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class ExamTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Subject $subject;

    private QuestionType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->subject = Subject::factory()->create(['user_id' => $this->user->id]);
        $this->type = QuestionType::factory()->create();
    }

    public function test_index_lists_only_the_authenticated_users_exams(): void
    {
        $mine = Exam::factory()->create(['user_id' => $this->user->id]);
        $other = User::factory()->create();
        Exam::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->get(route('exams.index'));

        $response->assertInertia(
            fn ($page) => $page
                ->component('Exams/Index')
                ->has('exams.data', 1)
                ->where('exams.data.0.id', $mine->id)
        );
    }

    public function test_index_filters_by_search_term(): void
    {
        $match = Exam::factory()->create(['user_id' => $this->user->id, 'title' => 'Prova de Geografia']);
        Exam::factory()->create(['user_id' => $this->user->id, 'title' => 'Prova de História']);

        $response = $this->actingAs($this->user)->get(route('exams.index', ['search' => 'Geografia']));

        $response->assertInertia(
            fn ($page) => $page
                ->has('exams.data', 1)
                ->where('exams.data.0.id', $match->id)
        );
    }

    public function test_index_sorts_by_a_whitelisted_column(): void
    {
        $older = Exam::factory()->create(['user_id' => $this->user->id, 'title' => 'A - Prova']);
        $newer = Exam::factory()->create(['user_id' => $this->user->id, 'title' => 'Z - Prova']);

        $response = $this->actingAs($this->user)->get(
            route('exams.index', ['sort' => 'title', 'direction' => 'asc'])
        );

        $response->assertInertia(
            fn ($page) => $page
                ->where('exams.data.0.id', $older->id)
                ->where('exams.data.1.id', $newer->id)
        );
    }

    public function test_store_creates_an_exam_with_questions(): void
    {
        $question = $this->createQuestion();

        $payload = [
            'title' => 'Prova Bimestral de Matemática',
            'description' => 'Primeiro bimestre',
            'main_subject_id' => $this->subject->id,
            'questions' => [
                ['question_id' => $question->id, 'order' => 1, 'points_override' => 5],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('exams.store'), $payload);

        $exam = Exam::where('title', 'Prova Bimestral de Matemática')->first();
        $response->assertRedirect(route('exams.show', $exam));
        $this->assertNotNull($exam);
        $this->assertSame(1, $exam->questions()->count());
        $this->assertEquals(5, $exam->fresh()->total_points);
    }

    public function test_a_user_cannot_view_another_users_exam(): void
    {
        $other = User::factory()->create();
        $exam = Exam::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->get(route('exams.show', $exam));

        $response->assertForbidden();
    }

    public function test_a_user_cannot_edit_another_users_exam(): void
    {
        $other = User::factory()->create();
        $exam = Exam::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->get(route('exams.edit', $exam));

        $response->assertForbidden();
    }

    public function test_a_user_cannot_delete_another_users_exam(): void
    {
        $other = User::factory()->create();
        $exam = Exam::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->delete(route('exams.destroy', $exam));

        $response->assertForbidden();
        $this->assertDatabaseHas('exams', ['id' => $exam->id, 'deleted_at' => null]);
    }

    public function test_destroy_removes_the_exam_and_detaches_questions(): void
    {
        $exam = Exam::factory()->create(['user_id' => $this->user->id]);
        $question = $this->createQuestion();
        $exam->questions()->attach($question->id, ['order' => 1]);

        $response = $this->actingAs($this->user)->delete(route('exams.destroy', $exam));

        $response->assertRedirect(route('exams.index'));
        $this->assertSoftDeleted($exam);
        $this->assertDatabaseMissing('exam_questions', ['exam_id' => $exam->id]);
    }

    public function test_update_with_full_payload_succeeds_and_recalculates_points(): void
    {
        $exam = Exam::factory()->create(['user_id' => $this->user->id, 'main_subject_id' => $this->subject->id]);
        $question = $this->createQuestion();

        $payload = [
            'title' => 'Título Atualizado',
            'main_subject_id' => $this->subject->id,
            'questions' => [
                ['question_id' => $question->id, 'order' => 1, 'points_override' => 8],
            ],
        ];

        $response = $this->actingAs($this->user)->put(route('exams.update', $exam), $payload);

        $response->assertRedirect(route('exams.show', $exam));
        $this->assertSame('Título Atualizado', $exam->fresh()->title);
        $this->assertEquals(8, $exam->fresh()->total_points);
    }

    /**
     * resources/js/Pages/Exams/Edit.vue's useForm() now mirrors
     * main_subject_id and format_config from the exam being edited (the
     * quick-edit modal never lets the user change either), so this exact
     * payload shape should save cleanly.
     */
    public function test_update_accepts_the_payload_sent_by_the_edit_screen(): void
    {
        $exam = Exam::factory()->create(['user_id' => $this->user->id, 'main_subject_id' => $this->subject->id]);
        $question = $this->createQuestion();

        // Mirrors Exams/Edit.vue's useForm() fields exactly.
        $payload = [
            'title' => $exam->title,
            'description' => $exam->description,
            'exam_date' => $exam->exam_date,
            'main_subject_id' => $exam->main_subject_id,
            'target_total_points' => $exam->target_total_points,
            'header_config' => $exam->header_config,
            'format_config' => $exam->format_config,
            'footer_config' => $exam->footer_config,
            'questions' => [
                ['question_id' => $question->id, 'order' => 1, 'points_override' => null],
            ],
        ];

        $response = $this->actingAs($this->user)->put(route('exams.update', $exam), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('exams.show', $exam));
    }

    /**
     * BUG: ExamController::store() validates questions.*.question_id with a
     * plain `exists:questions,id` rule, so a professor can build an exam out
     * of another professor's private questions.
     */
    public function test_store_rejects_questions_owned_by_another_user(): void
    {
        $other = User::factory()->create();
        $otherSubject = Subject::factory()->create(['user_id' => $other->id]);
        $foreignQuestion = Question::factory()->create([
            'user_id' => $other->id,
            'subject_id' => $otherSubject->id,
            'topic_id' => null,
            'question_type_id' => $this->type->id,
        ]);

        $payload = [
            'title' => 'Prova com questão alheia',
            'main_subject_id' => $this->subject->id,
            'questions' => [
                ['question_id' => $foreignQuestion->id, 'order' => 1],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('exams.store'), $payload);

        $response->assertSessionHasErrors('questions.0.question_id');
        $this->assertDatabaseMissing('exams', ['title' => 'Prova com questão alheia']);
    }

    public function test_export_pdf_is_forbidden_for_non_owner(): void
    {
        $other = User::factory()->create();
        $exam = Exam::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->get(route('exams.export.pdf', $exam));

        $response->assertForbidden();
    }

    public function test_export_pdf_downloads_for_the_owner(): void
    {
        $exam = Exam::factory()->create(['user_id' => $this->user->id]);
        $question = $this->createQuestion();
        $exam->questions()->attach($question->id, ['order' => 1]);

        $response = $this->actingAs($this->user)->get(route('exams.export.pdf', $exam));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    /**
     * ExamController::exportDocx() calls PHP's exit() after streaming the
     * file, which would terminate the whole PHPUnit process if it ran
     * in-process. Isolate it so a real bug in the controller can't take
     * down the rest of the suite.
     */
    #[RunInSeparateProcess]
    public function test_export_docx_downloads_for_the_owner(): void
    {
        $exam = Exam::factory()->create(['user_id' => $this->user->id]);
        $question = $this->createQuestion();
        $exam->questions()->attach($question->id, ['order' => 1]);

        $response = $this->actingAs($this->user)->get(route('exams.export.docx', $exam));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );
    }

    /**
     * BUG found via PHPStan/Larastan: the two-column layout used to call
     * Section::addColumnBreak(), a method that doesn't exist in the
     * installed PhpWord version, so exporting with format_config.columns=2
     * always threw a fatal error.
     */
    public function test_export_docx_with_two_columns_downloads_for_the_owner(): void
    {
        $exam = Exam::factory()->create([
            'user_id' => $this->user->id,
            'format_config' => ['columns' => 2],
        ]);
        $question = $this->createQuestion();
        $exam->questions()->attach($question->id, ['order' => 1]);

        $response = $this->actingAs($this->user)->get(route('exams.export.docx', $exam));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );
    }

    public function test_questions_endpoint_returns_the_exams_questions(): void
    {
        $exam = Exam::factory()->create(['user_id' => $this->user->id]);
        $question = $this->createQuestion();
        $exam->questions()->attach($question->id, ['order' => 1]);

        $response = $this->actingAs($this->user)->get(route('exams.questions', $exam));

        $response->assertOk();
        $response->assertJsonCount(1, 'questions');
        $response->assertJsonPath('questions.0.id', $question->id);
    }

    public function test_questions_endpoint_is_forbidden_for_non_owner(): void
    {
        $other = User::factory()->create();
        $exam = Exam::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->get(route('exams.questions', $exam));

        $response->assertForbidden();
    }

    public function test_toggle_publish_route_is_registered(): void
    {
        $this->assertTrue(
            Route::has('exams.toggle-publish'),
            'Expected a named route for ExamController::togglePublish(), none is registered.'
        );
    }

    public function test_duplicate_route_is_registered(): void
    {
        $this->assertTrue(
            Route::has('exams.duplicate'),
            'Expected a named route for ExamController::duplicate(), none is registered.'
        );
    }

    private function createQuestion(): Question
    {
        return Question::factory()->create([
            'user_id' => $this->user->id,
            'subject_id' => $this->subject->id,
            'topic_id' => null,
            'question_type_id' => $this->type->id,
        ]);
    }
}
