<?php

namespace Tests\Unit;

use App\Jobs\ProcessDocumentExtractionJob;
use App\Models\Document;
use App\Models\User;
use App\Services\ClaudeExtractorService;
use App\Services\DocumentParserService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProcessDocumentExtractionJobTest extends TestCase
{
    use RefreshDatabase;

    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->document = Document::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_handle_marks_the_document_completed_and_stores_the_extraction_result(): void
    {
        $parser = Mockery::mock(DocumentParserService::class);
        $parser->shouldReceive('extractText')
            ->once()
            ->with($this->document->file_path, $this->document->file_type)
            ->andReturn('Texto extraído do documento.');

        $extracted = [
            'questions' => [
                ['statement' => 'Pergunta válida?', 'type' => 'essay', 'confidence' => 0.9],
            ],
            'metadata' => ['total_questions' => 1, 'warnings' => []],
        ];

        $extractor = Mockery::mock(ClaudeExtractorService::class);
        $extractor->shouldReceive('extractQuestions')->once()->andReturn($extracted);
        $extractor->shouldReceive('validateExtraction')->once()->andReturn([]);

        (new ProcessDocumentExtractionJob($this->document))->handle($parser, $extractor);

        $this->document->refresh();
        $this->assertSame('completed', $this->document->status);
        $this->assertNull($this->document->error_message);
        $this->assertSame($extracted, $this->document->extraction_result);
    }

    public function test_handle_merges_validation_warnings_into_the_metadata(): void
    {
        $parser = Mockery::mock(DocumentParserService::class);
        $parser->shouldReceive('extractText')->once()->andReturn('Texto.');

        $extractor = Mockery::mock(ClaudeExtractorService::class);
        $extractor->shouldReceive('extractQuestions')->once()->andReturn([
            'questions' => [['statement' => '', 'type' => 'essay']],
            'metadata' => ['total_questions' => 1, 'warnings' => []],
        ]);
        $extractor->shouldReceive('validateExtraction')->once()->andReturn(['Questão 1: enunciado vazio']);

        (new ProcessDocumentExtractionJob($this->document))->handle($parser, $extractor);

        $this->document->refresh();
        $this->assertSame('completed', $this->document->status);
        $this->assertContains('Questão 1: enunciado vazio', $this->document->extraction_result['metadata']['warnings']);
    }

    public function test_handle_marks_the_document_failed_when_text_extraction_throws(): void
    {
        $parser = Mockery::mock(DocumentParserService::class);
        $parser->shouldReceive('extractText')->once()->andThrow(new Exception('PDF corrompido.'));

        $extractor = Mockery::mock(ClaudeExtractorService::class);
        $extractor->shouldNotReceive('extractQuestions');

        try {
            (new ProcessDocumentExtractionJob($this->document))->handle($parser, $extractor);
            $this->fail('Expected handle() to rethrow the parser exception.');
        } catch (Exception $e) {
            $this->assertSame('PDF corrompido.', $e->getMessage());
        }

        $this->document->refresh();
        $this->assertSame('failed', $this->document->status);
        $this->assertSame('PDF corrompido.', $this->document->error_message);
    }

    public function test_handle_marks_the_document_failed_when_no_text_can_be_extracted(): void
    {
        $parser = Mockery::mock(DocumentParserService::class);
        $parser->shouldReceive('extractText')->once()->andReturn('   ');

        $extractor = Mockery::mock(ClaudeExtractorService::class);
        $extractor->shouldNotReceive('extractQuestions');

        try {
            (new ProcessDocumentExtractionJob($this->document))->handle($parser, $extractor);
            $this->fail('Expected handle() to throw for a document with no extractable text.');
        } catch (Exception $e) {
            $this->assertSame('Documento não contém texto extraível', $e->getMessage());
        }

        $this->assertSame('failed', $this->document->fresh()->status);
    }

    public function test_failed_hook_marks_the_document_failed_with_a_final_message(): void
    {
        $job = new ProcessDocumentExtractionJob($this->document);

        $job->failed(new Exception('Timeout na API do Claude.'));

        $this->document->refresh();
        $this->assertSame('failed', $this->document->status);
        $this->assertStringContainsString('Timeout na API do Claude.', $this->document->error_message);
    }
}
