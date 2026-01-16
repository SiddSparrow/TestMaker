<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Messages\MessageParam;
use Exception;
use Illuminate\Support\Facades\Log;

class ClaudeExtractorService
{
    private Client $client;

    private string $model = 'claude-sonnet-4-5-20250929'; // Claude 3.5 Sonnet

    public function __construct()
    {
        $apiKey = config('services.claude.key');

        if (empty($apiKey)) {
            throw new Exception('CLAUDE_KEY não configurada no .env');
        }

        // Inicializar cliente Anthropic SDK
        $this->client = new Client($apiKey);
    }

    /**
     * Extrai questões de um texto usando Claude API via SDK oficial
     * Detecta automaticamente se precisa dividir em chunks
     */
    public function extractQuestions(string $documentText): array
    {
        // Primeiro, detectar quantas questões aproximadamente existem
        $estimatedQuestions = $this->estimateQuestionCount($documentText);

        Log::info('Estimativa de questões no documento', [
            'estimated_count' => $estimatedQuestions,
        ]);

        // Se tiver mais de 20 questões, processar em chunks
        if ($estimatedQuestions > 20) {
            return $this->extractQuestionsInChunks($documentText, $estimatedQuestions);
        }

        // Processar normalmente se tiver até 20 questões
        return $this->extractQuestionsFromText($documentText);
    }

    /**
     * Estima quantas questões existem no documento
     */
    private function estimateQuestionCount(string $documentText): int
    {
        // Contar padrões comuns de numeração de questões
        $patterns = [
            '/(?:^|\n)\s*(?:QUESTÃO|QUESTAO|Question|Q\.?)\s*(\d+)/mi',
            '/(?:^|\n)\s*(\d+)\s*[.)\-]\s*[A-Z]/m',
            '/(?:^|\n)\s*(\d+)\s*[.)\-]\s*(?:Qual|Como|Por que|Onde|Quando)/mi',
        ];

        $maxCount = 0;
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $documentText, $matches);
            if (!empty($matches[1])) {
                $numbers = array_map('intval', $matches[1]);
                $maxCount = max($maxCount, max($numbers));
            }
        }

        return $maxCount;
    }

    /**
     * Extrai questões dividindo o documento em chunks
     */
    private function extractQuestionsInChunks(string $documentText, int $totalQuestions): array
    {
        $chunkSize = 15; // Questões por chunk
        $chunks = ceil($totalQuestions / $chunkSize);

        Log::info('Processando documento em múltiplos chunks', [
            'total_questions' => $totalQuestions,
            'chunks' => $chunks,
            'questions_per_chunk' => $chunkSize,
        ]);

        $allQuestions = [];
        $allWarnings = [];

        for ($i = 0; $i < $chunks; $i++) {
            $startQuestion = ($i * $chunkSize) + 1;
            $endQuestion = min(($i + 1) * $chunkSize, $totalQuestions);

            Log::info('Processando chunk ' . ($i + 1) . "/{$chunks}", [
                'questions_range' => "{$startQuestion}-{$endQuestion}",
            ]);

            try {
                $chunkData = $this->extractQuestionsFromText(
                    $documentText,
                    $startQuestion,
                    $endQuestion
                );

                if (!empty($chunkData['questions'])) {
                    $allQuestions = array_merge($allQuestions, $chunkData['questions']);
                }

                if (!empty($chunkData['metadata']['warnings'])) {
                    $allWarnings = array_merge($allWarnings, $chunkData['metadata']['warnings']);
                }
            } catch (Exception $e) {
                Log::warning('Erro ao processar chunk ' . ($i + 1), [
                    'error' => $e->getMessage(),
                ]);
                $allWarnings[] = "Chunk {$startQuestion}-{$endQuestion}: " . $e->getMessage();
            }
        }

        return [
            'questions' => $allQuestions,
            'metadata' => [
                'total_questions' => count($allQuestions),
                'exam_title' => '',
                'warnings' => $allWarnings,
                'processed_in_chunks' => true,
                'chunks_count' => $chunks,
            ],
        ];
    }

    /**
     * Extrai questões de um texto (método interno)
     */
    private function extractQuestionsFromText(string $documentText, ?int $startQuestion = null, ?int $endQuestion = null): array
    {
        $prompt = $this->buildPrompt($documentText, $startQuestion, $endQuestion);

        try {
            Log::info('Enviando requisição para Claude API', [
                'model' => $this->model,
                'text_length' => strlen($documentText),
                'question_range' => $startQuestion && $endQuestion ? "{$startQuestion}-{$endQuestion}" : 'all',
            ]);

            // Criar mensagem usando SDK oficial
            $response = $this->client->messages->create(
                model: $this->model,
                maxTokens: 8192,
                messages: [
                    MessageParam::with(role: 'user', content: $prompt),
                ],
            );

            // Extrair texto da resposta
            $contentText = $response->content[0]->text ?? '';

            if (empty($contentText)) {
                throw new Exception('Resposta do Claude está vazia');
            }

            Log::info('Resposta recebida do Claude', [
                'response_length' => strlen($contentText),
            ]);

            // Extrair JSON do texto (Claude pode adicionar markdown)
            $jsonText = $contentText;

            // Tentar extrair de blocos markdown
            if (preg_match('/```json\s*(.*?)\s*```/s', $contentText, $jsonMatch)) {
                $jsonText = trim($jsonMatch[1]);
            } elseif (preg_match('/```\s*(.*?)\s*```/s', $contentText, $jsonMatch)) {
                $jsonText = trim($jsonMatch[1]);
            } else {
                // Remover possíveis textos antes/depois do JSON
                $jsonText = trim($contentText);
                // Se começar com texto e depois {, pegar só a parte JSON
                if (preg_match('/(\{.*\})/s', $jsonText, $jsonMatch)) {
                    $jsonText = $jsonMatch[1];
                }
            }

            // Tentar decodificar
            $extractedData = json_decode($jsonText, true);

            if (json_last_error() !== JSON_ERROR_NONE || empty($extractedData)) {
                // Log detalhado para debug
                Log::error('Erro ao decodificar JSON do Claude', [
                    'json_error' => json_last_error_msg(),
                    'content_length' => strlen($contentText),
                    'json_length' => strlen($jsonText),
                    'first_200_chars' => substr($jsonText, 0, 200),
                    'last_200_chars' => substr($jsonText, -200),
                ]);
                throw new Exception(
                    'Resposta do Claude não está em JSON válido. ' .
                    'JSON Error: ' . json_last_error_msg() . '. ' .
                    'Primeiros caracteres: ' . substr($jsonText, 0, 100)
                );
            }

            Log::info('Questões extraídas com sucesso', [
                'total_questions' => count($extractedData['questions'] ?? []),
            ]);

            return $extractedData;
        } catch (Exception $e) {
            Log::error('Erro ao extrair questões com Claude', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Constrói o prompt especializado para extração de questões
     */
    private function buildPrompt(string $documentText, ?int $startQuestion = null, ?int $endQuestion = null): string
    {
        $rangeInstruction = '';
        if ($startQuestion && $endQuestion) {
            $rangeInstruction = "\n**IMPORTANTE: Extraia APENAS as questões de número {$startQuestion} até {$endQuestion}. Ignore todas as outras questões.**\n";
        }

        return <<<PROMPT
Você é um assistente especializado em extrair questões de provas acadêmicas.

TAREFA: Analise o documento abaixo e extraia as questões no formato JSON estruturado.
{$rangeInstruction}
REGRAS IMPORTANTES:
1. Identifique o enunciado completo de cada questão (incluindo texto introdutório)
2. Extraia todas as alternativas (A, B, C, D, E ou a, b, c, d, e ou I, II, III, IV)
3. Detecte a resposta correta se houver gabarito no documento
4. Preserve formatação matemática (use LaTeX entre \$ se necessário)
5. Identifique o tipo: multiple_choice, true_false, ou essay
6. Infira dificuldade: easy, medium, ou hard
7. Se possível, infira disciplina e tópico baseado no conteúdo

FORMATO DE SAÍDA (JSON válido):
{
  "questions": [
    {
      "number": 1,
      "statement": "Enunciado completo da questão...",
      "type": "multiple_choice",
      "alternatives": [
        {"letter": "A", "content": "Texto alternativa A", "is_correct": false},
        {"letter": "B", "content": "Texto alternativa B", "is_correct": true}
      ],
      "explanation": "",
      "subject_hint": "Matemática",
      "topic_hint": "Geometria",
      "difficulty_hint": "medium",
      "confidence": 0.95
    }
  ],
  "metadata": {
    "total_questions": 1,
    "exam_title": "",
    "warnings": []
  }
}

IMPORTANTE - VALIDAÇÃO DO JSON:
- Certifique-se de que o JSON está COMPLETO e VÁLIDO
- Feche todos os colchetes e chaves corretamente
- Use aspas duplas para strings
- Não use vírgulas no último item de arrays/objetos
- Se não houver explanation, use string vazia "" (não omita o campo)
- Para questões dissertativas: "type": "essay", "alternatives": []
- Para verdadeiro/falso: "type": "true_false"
- confidence deve ser número entre 0 e 1
- Se não souber a resposta correta, coloque todos is_correct como false e adicione warning

DOCUMENTO:
{$documentText}

Retorne APENAS o JSON válido, sem texto adicional. Envolva o JSON em um bloco markdown:
```json
{ seu JSON aqui }
```
PROMPT;
    }

    /**
     * Valida a estrutura do JSON extraído
     */
    public function validateExtraction(array $data): array
    {
        $errors = [];

        if (!isset($data['questions']) || !is_array($data['questions'])) {
            $errors[] = 'Estrutura inválida: campo "questions" não encontrado';

            return $errors;
        }

        foreach ($data['questions'] as $index => $question) {
            $qNum = $index + 1;

            if (empty($question['statement'])) {
                $errors[] = "Questão {$qNum}: enunciado vazio";
            }

            if (empty($question['type'])) {
                $errors[] = "Questão {$qNum}: tipo não especificado";
            }

            if ($question['type'] === 'multiple_choice') {
                if (empty($question['alternatives']) || count($question['alternatives']) < 2) {
                    $errors[] = "Questão {$qNum}: múltipla escolha deve ter pelo menos 2 alternativas";
                }

                // Verificar se tem pelo menos uma correta (aviso, não erro)
                $hasCorrect = false;
                if (!empty($question['alternatives'])) {
                    foreach ($question['alternatives'] as $alt) {
                        if ($alt['is_correct'] ?? false) {
                            $hasCorrect = true;
                            break;
                        }
                    }
                }
            }

            if (isset($question['confidence']) && ($question['confidence'] < 0 || $question['confidence'] > 1)) {
                $errors[] = "Questão {$qNum}: confiança deve estar entre 0 e 1";
            }
        }

        return $errors;
    }
}
