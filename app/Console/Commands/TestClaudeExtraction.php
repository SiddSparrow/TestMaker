<?php

namespace App\Console\Commands;

use App\Services\ClaudeExtractorService;
use Exception;
use Illuminate\Console\Command;

/**
 * Substitui o script test-extraction.php que vivia na raiz do projeto —
 * fazia sua própria inicialização manual do kernel e não passava por
 * nenhuma configuração de ambiente do artisan.
 */
class TestClaudeExtraction extends Command
{
    protected $signature = 'claude:test-extraction';

    protected $description = 'Testa manualmente a extração de questões via ClaudeExtractorService com um texto de exemplo (faz uma chamada real à API)';

    public function handle(): int
    {
        $sampleText = <<<'TEXT'
        PROVA DE MATEMÁTICA - 2º BIMESTRE

        QUESTÃO 1
        Qual é o resultado de 2 + 2?

        A) 3
        B) 4
        C) 5
        D) 6

        GABARITO: B

        QUESTÃO 2
        A raiz quadrada de 16 é:

        A) 2
        B) 4
        C) 8
        D) 16

        GABARITO: B
        TEXT;

        $this->info('Testando ClaudeExtractorService...');
        $this->line('CLAUDE_KEY configurada: ' . (config('services.claude.key') ? 'sim' : 'não'));

        try {
            $extractor = new ClaudeExtractorService;
            $result = $extractor->extractQuestions($sampleText);

            $this->newLine();
            $this->info('Total de questões extraídas: ' . count($result['questions'] ?? []));

            foreach ($result['questions'] ?? [] as $index => $question) {
                $this->line(sprintf(
                    '%d. [%s] %s',
                    $index + 1,
                    $question['type'] ?? 'N/A',
                    \Illuminate\Support\Str::limit($question['statement'] ?? '', 80)
                ));
            }

            $errors = $extractor->validateExtraction($result);
            if (empty($errors)) {
                $this->info('Nenhum erro de validação.');
            } else {
                $this->warn('Erros de validação encontradas:');
                foreach ($errors as $error) {
                    $this->line('  - ' . $error);
                }
            }

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Erro: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
