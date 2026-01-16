<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentParserService
{
    /**
     * Extrai texto de um documento baseado no tipo
     */
    public function extractText(string $filePath, string $fileType): string
    {
        $fullPath = Storage::path($filePath);

        if (!file_exists($fullPath)) {
            throw new Exception("Arquivo não encontrado: {$filePath}");
        }

        return match (strtolower($fileType)) {
            'pdf' => $this->extractFromPdf($fullPath),
            'docx' => $this->extractFromDocx($fullPath),
            'doc' => $this->extractFromDoc($fullPath),
            'txt' => $this->extractFromTxt($fullPath),
            default => throw new Exception("Tipo de arquivo não suportado: {$fileType}"),
        };
    }

    /**
     * Extrai texto de PDF
     */
    private function extractFromPdf(string $filePath): string
    {
        try {
            // Usando smalot/pdfparser
            $parser = new \Smalot\PdfParser\Parser;
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            if (empty(trim($text))) {
                throw new Exception('PDF parece estar vazio ou ser escaneado (imagem). OCR não implementado neste MVP.');
            }

            return $this->cleanText($text);
        } catch (Exception $e) {
            Log::error('Erro ao extrair texto de PDF', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Erro ao processar PDF: ' . $e->getMessage());
        }
    }

    /**
     * Extrai texto de DOCX
     */
    private function extractFromDocx(string $filePath): string
    {
        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
            $text = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    } elseif (method_exists($element, 'getElements')) {
                        // Para tabelas e containers
                        $text .= $this->extractFromContainer($element) . "\n";
                    }
                }
            }

            if (empty(trim($text))) {
                throw new Exception('DOCX parece estar vazio.');
            }

            return $this->cleanText($text);
        } catch (Exception $e) {
            Log::error('Erro ao extrair texto de DOCX', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Erro ao processar DOCX: ' . $e->getMessage());
        }
    }

    /**
     * Extrai texto de elementos container (tabelas, etc)
     */
    private function extractFromContainer($container): string
    {
        $text = '';

        if (!method_exists($container, 'getElements')) {
            return $text;
        }

        foreach ($container->getElements() as $element) {
            if (method_exists($element, 'getText')) {
                $text .= $element->getText() . ' ';
            } elseif (method_exists($element, 'getElements')) {
                $text .= $this->extractFromContainer($element);
            }
        }

        return $text;
    }

    /**
     * Extrai texto de DOC (formato antigo)
     */
    private function extractFromDoc(string $filePath): string
    {
        // Para DOC antigo, precisaria de antiword ou conversão
        // Por enquanto, sugerir ao usuário salvar como DOCX
        throw new Exception('Formato .DOC não suportado. Por favor, salve como .DOCX e tente novamente.');
    }

    /**
     * Extrai texto de TXT
     */
    private function extractFromTxt(string $filePath): string
    {
        $text = file_get_contents($filePath);

        if ($text === false) {
            throw new Exception('Erro ao ler arquivo TXT');
        }

        return $this->cleanText($text);
    }

    /**
     * Limpa e normaliza o texto extraído
     */
    private function cleanText(string $text): string
    {
        // Remove múltiplas quebras de linha
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // Remove espaços múltiplos
        $text = preg_replace('/[ \t]+/', ' ', $text);

        // Remove espaços no início/fim de cada linha
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        $text = implode("\n", $lines);

        return trim($text);
    }

    /**
     * Valida se o arquivo é processável
     */
    public function validateFile(string $filePath, string $fileType): bool
    {
        $supportedTypes = ['pdf', 'docx', 'txt'];

        if (!in_array(strtolower($fileType), $supportedTypes)) {
            return false;
        }

        $fullPath = Storage::path($filePath);

        if (!file_exists($fullPath)) {
            return false;
        }

        // Verificar tamanho (max 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if (filesize($fullPath) > $maxSize) {
            return false;
        }

        return true;
    }
}
