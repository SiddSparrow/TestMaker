<?php

namespace Tests\Unit;

use App\Services\ClaudeExtractorService;
use ReflectionClass;
use Tests\TestCase;

/**
 * Exercises the parts of ClaudeExtractorService that are pure logic
 * (question-count estimation, extraction validation) via reflection, so
 * the suite never makes a real call to the Anthropic API. The private
 * methods that do call the API (extractQuestionsFromText et al.) are
 * covered indirectly through ProcessDocumentExtractionJobTest, where the
 * service itself is mocked.
 */
class ClaudeExtractorServiceTest extends TestCase
{
    private ClaudeExtractorService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // The constructor throws if this is empty; it never needs to be a
        // real key because we never call a method that hits the network.
        config(['services.claude.key' => 'test-key-not-a-real-anthropic-key']);

        $this->service = new ClaudeExtractorService;
    }

    private function callPrivate(string $method, array $args = [])
    {
        $reflectionMethod = (new ReflectionClass($this->service))->getMethod($method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($this->service, $args);
    }

    public function test_estimate_question_count_returns_zero_for_text_without_numbered_questions(): void
    {
        $this->assertSame(
            0,
            $this->callPrivate('estimateQuestionCount', ['Texto qualquer sem questões numeradas.'])
        );
    }

    public function test_estimate_question_count_finds_the_highest_explicitly_numbered_question(): void
    {
        $text = "QUESTÃO 1\nEnunciado...\n\nQUESTÃO 2\nEnunciado...\n\nQUESTÃO 5\nEnunciado...";

        $this->assertSame(5, $this->callPrivate('estimateQuestionCount', [$text]));
    }

    /**
     * KNOWN LIMITATION (docs/avaliacao-completude.md): estimateQuestionCount()
     * takes the MAXIMUM number matched by a very loose "digit + punctuation +
     * capital letter" pattern. A single stray numbered line unrelated to
     * questions ("150) Alguma coisa") makes the estimator believe there are
     * 150 questions in what is really a one-question document, which
     * triggers unnecessary chunked extraction (10 extra full-document
     * round-trips to the Claude API, at 10x the token cost). This test
     * characterises today's behaviour precisely — it passes now — so the
     * regex can't silently get worse, and so the fix (cap the estimate, or
     * count matches instead of taking their max) has a test to flip.
     */
    public function test_estimate_question_count_overestimates_from_a_single_stray_numbered_line(): void
    {
        $text = "1. Qual a capital da França?\n\n150) Isto não é uma questão, é só uma frase numerada.";

        $this->assertSame(150, $this->callPrivate('estimateQuestionCount', [$text]));
    }

    public function test_validate_extraction_reports_missing_questions_key(): void
    {
        $errors = $this->service->validateExtraction([]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('questions', $errors[0]);
    }

    public function test_validate_extraction_reports_empty_statement(): void
    {
        $errors = $this->service->validateExtraction([
            'questions' => [
                ['statement' => '', 'type' => 'essay'],
            ],
        ]);

        $this->assertContains('Questão 1: enunciado vazio', $errors);
    }

    public function test_validate_extraction_requires_at_least_two_alternatives_for_multiple_choice(): void
    {
        $errors = $this->service->validateExtraction([
            'questions' => [
                [
                    'statement' => 'Pergunta válida?',
                    'type' => 'multiple_choice',
                    'alternatives' => [['letter' => 'A', 'content' => 'Única', 'is_correct' => true]],
                ],
            ],
        ]);

        $this->assertContains(
            'Questão 1: múltipla escolha deve ter pelo menos 2 alternativas',
            $errors
        );
    }

    public function test_validate_extraction_reports_confidence_out_of_range(): void
    {
        $errors = $this->service->validateExtraction([
            'questions' => [
                [
                    'statement' => 'Pergunta válida?',
                    'type' => 'essay',
                    'confidence' => 1.5,
                ],
            ],
        ]);

        $this->assertContains('Questão 1: confiança deve estar entre 0 e 1', $errors);
    }

    public function test_validate_extraction_accepts_a_well_formed_multiple_choice_question(): void
    {
        $errors = $this->service->validateExtraction([
            'questions' => [
                [
                    'statement' => 'Pergunta válida?',
                    'type' => 'multiple_choice',
                    'confidence' => 0.9,
                    'alternatives' => [
                        ['letter' => 'A', 'content' => 'Errada', 'is_correct' => false],
                        ['letter' => 'B', 'content' => 'Certa', 'is_correct' => true],
                    ],
                ],
            ],
        ]);

        $this->assertSame([], $errors);
    }
}
