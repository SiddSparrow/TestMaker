<?php

/**
 * Script de teste para extração de questões
 * Execute: php test-extraction.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\ClaudeExtractorService;

echo "===========================================\n";
echo "  TESTE DE EXTRAÇÃO DE QUESTÕES\n";
echo "===========================================\n\n";

// Texto de exemplo
$textoTeste = <<<'TEXT'
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

echo "1. Testando ClaudeExtractorService...\n";
echo "--------------------------------------\n\n";

try {
    $extractor = new ClaudeExtractorService;
    echo "✓ ClaudeExtractorService instanciado\n";
    echo '✓ CLAUDE_KEY configurada: ' . (config('services.claude.key') ? 'SIM' : 'NÃO') . "\n\n";

    echo "2. Enviando para Claude API...\n";
    echo "--------------------------------------\n";
    echo "Texto a processar:\n";
    echo substr($textoTeste, 0, 200) . "...\n\n";

    $resultado = $extractor->extractQuestions($textoTeste);

    echo "✓ Resposta recebida do Claude!\n\n";

    echo "3. Resultado da Extração:\n";
    echo "--------------------------------------\n";
    echo 'Total de questões: ' . count($resultado['questions'] ?? []) . "\n\n";

    if (isset($resultado['questions'])) {
        foreach ($resultado['questions'] as $index => $questao) {
            echo 'Questão ' . ($index + 1) . ":\n";
            echo '  Enunciado: ' . substr($questao['statement'], 0, 80) . "...\n";
            echo '  Tipo: ' . ($questao['type'] ?? 'N/A') . "\n";
            echo '  Dificuldade: ' . ($questao['difficulty_hint'] ?? 'N/A') . "\n";
            echo '  Confiança: ' . (isset($questao['confidence']) ? round($questao['confidence'] * 100) . '%' : 'N/A') . "\n";
            echo '  Alternativas: ' . count($questao['alternatives'] ?? []) . "\n";

            if (!empty($questao['alternatives'])) {
                foreach ($questao['alternatives'] as $alt) {
                    $correta = ($alt['is_correct'] ?? false) ? ' ✓' : '';
                    echo "    {$alt['letter']}) " . substr($alt['content'], 0, 50) . $correta . "\n";
                }
            }
            echo "\n";
        }
    }

    echo "4. Validação:\n";
    echo "--------------------------------------\n";
    $errors = $extractor->validateExtraction($resultado);
    if (empty($errors)) {
        echo "✓ Nenhum erro de validação!\n";
    } else {
        echo "⚠ Erros encontrados:\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
    }

    echo "\n5. JSON Completo:\n";
    echo "--------------------------------------\n";
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "\n\n";

    echo "===========================================\n";
    echo "✓ TESTE CONCLUÍDO COM SUCESSO!\n";
    echo "===========================================\n";
} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n\n";
    echo "Stacktrace:\n";
    echo $e->getTraceAsString() . "\n\n";

    echo "Verificações:\n";
    echo '1. CLAUDE_KEY está no .env? ' . (env('CLAUDE_KEY') ? 'SIM' : 'NÃO') . "\n";
    echo '2. Composer instalou smalot/pdfparser? ' . (class_exists('Smalot\PdfParser\Parser') ? 'SIM' : 'NÃO') . "\n";
    echo '3. Composer instalou phpoffice/phpword? ' . (class_exists('PhpOffice\PhpWord\IOFactory') ? 'SIM' : 'NÃO') . "\n";

    exit(1);
}

echo "\n6. Verificando Logs:\n";
echo "--------------------------------------\n";
echo "Logs serão salvos em: storage/logs/laravel.log\n";
echo "Para ver logs em tempo real, execute em outro terminal:\n";
echo "  tail -f storage/logs/laravel.log\n\n";

echo "7. Próximos Passos:\n";
echo "--------------------------------------\n";
echo "1. Executar migrations: php artisan migrate\n";
echo "2. Iniciar servidor: php artisan serve\n";
echo "3. Iniciar queue worker: php artisan queue:work\n";
echo "4. Acessar: http://localhost:8000/documents\n";
echo "5. Fazer upload de um documento\n";
echo "6. Acompanhar logs: tail -f storage/logs/laravel.log\n\n";
