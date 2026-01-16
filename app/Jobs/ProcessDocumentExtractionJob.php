<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\ClaudeExtractorService;
use App\Services\DocumentParserService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDocumentExtractionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos
    public $tries = 2; // Tentar 2 vezes em caso de falha

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Document $document
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        DocumentParserService $parser,
        ClaudeExtractorService $extractor
    ): void
    {
        Log::info('Iniciando processamento de documento', [
            'document_id' => $this->document->id,
            'filename' => $this->document->original_name,
        ]);

        try {
            // Atualizar status para processing
            $this->document->update(['status' => 'processing']);

            // 1. Extrair texto do documento
            Log::info('Extraindo texto do documento', ['document_id' => $this->document->id]);

            $documentText = $parser->extractText(
                $this->document->file_path,
                $this->document->file_type
            );

            if (empty(trim($documentText))) {
                throw new Exception('Documento não contém texto extraível');
            }

            Log::info('Texto extraído com sucesso', [
                'document_id' => $this->document->id,
                'text_length' => strlen($documentText),
            ]);

            // 2. Enviar para Claude para extração de questões
            Log::info('Enviando para Claude API', ['document_id' => $this->document->id]);

            $extractedData = $extractor->extractQuestions($documentText);

            // 3. Validar estrutura
            $validationErrors = $extractor->validateExtraction($extractedData);

            if (!empty($validationErrors)) {
                Log::warning('Extração com avisos de validação', [
                    'document_id' => $this->document->id,
                    'errors' => $validationErrors,
                ]);

                // Adicionar warnings aos metadados
                if (!isset($extractedData['metadata'])) {
                    $extractedData['metadata'] = [];
                }
                if (!isset($extractedData['metadata']['warnings'])) {
                    $extractedData['metadata']['warnings'] = [];
                }
                $extractedData['metadata']['warnings'] = array_merge(
                    $extractedData['metadata']['warnings'],
                    $validationErrors
                );
            }

            // 4. Salvar resultado
            $this->document->update([
                'status' => 'completed',
                'extraction_result' => $extractedData,
                'error_message' => null,
            ]);

            Log::info('Documento processado com sucesso', [
                'document_id' => $this->document->id,
                'questions_found' => count($extractedData['questions'] ?? []),
            ]);

        } catch (Exception $e) {
            Log::error('Erro ao processar documento', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->document->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            // Re-lançar exceção para o Laravel registrar como job failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Job de extração falhou definitivamente', [
            'document_id' => $this->document->id,
            'error' => $exception->getMessage(),
        ]);

        $this->document->update([
            'status' => 'failed',
            'error_message' => 'Falha após múltiplas tentativas: ' . $exception->getMessage(),
        ]);
    }
}
