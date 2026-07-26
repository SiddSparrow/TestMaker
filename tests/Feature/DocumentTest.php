<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocumentExtractionJob;
use App\Models\Document;
use App\Models\QuestionType;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Storage::fake('local');
    }

    public function test_index_filters_by_search_and_status(): void
    {
        $match = Document::factory()->create([
            'user_id' => $this->user->id,
            'original_name' => 'prova-geografia.pdf',
            'status' => 'completed',
        ]);
        Document::factory()->create([
            'user_id' => $this->user->id,
            'original_name' => 'prova-historia.pdf',
            'status' => 'failed',
        ]);

        $response = $this->actingAs($this->user)->get(
            route('documents.index', ['search' => 'geografia', 'status' => 'completed'])
        );

        $response->assertInertia(
            fn ($page) => $page
                ->has('documents.data', 1)
                ->where('documents.data.0.id', $match->id)
        );
    }

    public function test_index_lists_only_the_authenticated_users_documents(): void
    {
        $mine = Document::factory()->create(['user_id' => $this->user->id]);
        $other = User::factory()->create();
        Document::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->get(route('documents.index'));

        $response->assertInertia(
            fn ($page) => $page
                ->has('documents.data', 1)
                ->where('documents.data.0.id', $mine->id)
        );
    }

    public function test_store_uploads_a_valid_document_and_dispatches_the_extraction_job(): void
    {
        Queue::fake();

        $file = UploadedFile::fake()->create('prova.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'document' => $file,
        ]);

        $response->assertRedirect(route('documents.index'));
        $this->assertDatabaseHas('documents', [
            'user_id' => $this->user->id,
            'original_name' => 'prova.pdf',
            'status' => 'pending',
        ]);
        Queue::assertPushed(ProcessDocumentExtractionJob::class);
    }

    public function test_store_rejects_unsupported_file_types(): void
    {
        $file = UploadedFile::fake()->create('prova.exe', 500, 'application/octet-stream');

        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'document' => $file,
        ]);

        $response->assertSessionHasErrors('document');
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_store_rejects_files_larger_than_10mb(): void
    {
        $file = UploadedFile::fake()->create('prova.pdf', 10241, 'application/pdf');

        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'document' => $file,
        ]);

        $response->assertSessionHasErrors('document');
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_a_user_cannot_view_another_users_document(): void
    {
        $other = User::factory()->create();
        $document = Document::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->get(route('documents.show', $document));

        $response->assertForbidden();
    }

    public function test_a_user_cannot_delete_another_users_document(): void
    {
        $other = User::factory()->create();
        $document = Document::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->delete(route('documents.destroy', $document));

        $response->assertForbidden();
        $this->assertDatabaseHas('documents', ['id' => $document->id]);
    }

    public function test_destroy_deletes_the_stored_file_and_the_record(): void
    {
        Storage::disk('local')->put('documents/fake.pdf', 'conteudo');
        $document = Document::factory()->create([
            'user_id' => $this->user->id,
            'file_path' => 'documents/fake.pdf',
        ]);

        $response = $this->actingAs($this->user)->delete(route('documents.destroy', $document));

        $response->assertRedirect(route('documents.index'));
        Storage::disk('local')->assertMissing('documents/fake.pdf');
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }

    public function test_reprocess_resets_status_and_redispatches_the_job(): void
    {
        Queue::fake();
        $document = Document::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'failed',
            'error_message' => 'Falha anterior',
        ]);

        $response = $this->actingAs($this->user)->post(route('documents.reprocess', $document));

        $response->assertRedirect();
        $this->assertSame('pending', $document->fresh()->status);
        $this->assertNull($document->fresh()->error_message);
        Queue::assertPushed(ProcessDocumentExtractionJob::class);
    }

    public function test_import_questions_creates_questions_and_stamps_imported_at(): void
    {
        $document = Document::factory()->create(['user_id' => $this->user->id, 'status' => 'completed']);
        $subject = Subject::factory()->create(['user_id' => $this->user->id]);
        $topic = Topic::factory()->create(['user_id' => $this->user->id, 'subject_id' => $subject->id]);
        QuestionType::factory()->create(['name' => 'Múltipla Escolha', 'slug' => 'multipla-escolha']);

        $payload = [
            'questions' => [
                [
                    'statement' => 'Questão extraída do documento pela IA?',
                    'type' => 'Múltipla Escolha',
                    'subject_id' => $subject->id,
                    'topic_id' => $topic->id,
                    'difficulty_level' => 'medium',
                    'points' => 2,
                    'alternatives' => [
                        ['content' => 'A', 'is_correct' => false],
                        ['content' => 'B', 'is_correct' => true],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('documents.import-questions', $document), $payload);

        $response->assertRedirect(route('questions.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('questions', 1);
        $this->assertSame('completed', $document->fresh()->status);
        $this->assertNotNull($document->fresh()->imported_at);
    }

    /**
     * The import validation now requires subject_id (scoped to the
     * authenticated user's own subjects) instead of declaring it nullable
     * while the questions table enforces NOT NULL.
     */
    public function test_import_questions_requires_a_subject(): void
    {
        $document = Document::factory()->create(['user_id' => $this->user->id, 'status' => 'completed']);
        QuestionType::factory()->create(['name' => 'Dissertativa', 'slug' => 'dissertativa']);

        $payload = [
            'questions' => [
                [
                    'statement' => 'Questão sem matéria selecionada.',
                    'type' => 'Dissertativa',
                    'difficulty_level' => 'easy',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('documents.import-questions', $document), $payload);

        $response->assertSessionHasErrors('questions.0.subject_id');
    }

    public function test_import_questions_is_forbidden_when_document_not_processed(): void
    {
        $document = Document::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);

        $response = $this->actingAs($this->user)->post(route('documents.import-questions', $document), [
            'questions' => [],
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('questions', 0);
    }
}
