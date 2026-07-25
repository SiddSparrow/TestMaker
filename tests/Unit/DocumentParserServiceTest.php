<?php

namespace Tests\Unit;

use App\Services\DocumentParserService;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class DocumentParserServiceTest extends TestCase
{
    private DocumentParserService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->service = new DocumentParserService;
    }

    public function test_extracts_and_cleans_text_from_a_txt_file(): void
    {
        Storage::disk('local')->put(
            'doc.txt',
            "Linha 1\n\n\n\nLinha  2   com    espaços     extras.  \n   Linha 3   "
        );

        $text = $this->service->extractText('doc.txt', 'txt');

        $this->assertStringContainsString("Linha 1\n\nLinha 2 com espaços extras.\nLinha 3", $text);
        $this->assertStringNotContainsString("\n\n\n", $text);
    }

    public function test_extracts_text_from_a_real_docx_file(): void
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $section->addText('Enunciado extraído de um DOCX real gerado no teste.');

        $tempPath = tempnam(sys_get_temp_dir(), 'docx');
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);
        Storage::disk('local')->put('doc.docx', file_get_contents($tempPath));
        unlink($tempPath);

        $text = $this->service->extractText('doc.docx', 'docx');

        $this->assertStringContainsString('Enunciado extraído de um DOCX real gerado no teste.', $text);
    }

    public function test_throws_for_empty_docx(): void
    {
        $phpWord = new PhpWord;
        $phpWord->addSection(); // no text elements at all

        $tempPath = tempnam(sys_get_temp_dir(), 'docx');
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);
        Storage::disk('local')->put('empty.docx', file_get_contents($tempPath));
        unlink($tempPath);

        $this->expectExceptionMessage('DOCX parece estar vazio.');

        $this->service->extractText('empty.docx', 'docx');
    }

    public function test_doc_format_is_never_supported(): void
    {
        Storage::disk('local')->put('legado.doc', 'qualquer conteúdo binário');

        $this->expectExceptionMessage('Formato .DOC não suportado. Por favor, salve como .DOCX e tente novamente.');

        $this->service->extractText('legado.doc', 'doc');
    }

    public function test_throws_for_unsupported_file_type(): void
    {
        Storage::disk('local')->put('doc.rtf', 'conteúdo');

        $this->expectExceptionMessage('Tipo de arquivo não suportado: rtf');

        $this->service->extractText('doc.rtf', 'rtf');
    }

    public function test_throws_when_file_does_not_exist(): void
    {
        $this->expectExceptionMessage('Arquivo não encontrado: nao-existe.pdf');

        $this->service->extractText('nao-existe.pdf', 'pdf');
    }

    public function test_validate_file_rejects_unsupported_extensions(): void
    {
        Storage::disk('local')->put('doc.rtf', 'conteúdo');

        $this->assertFalse($this->service->validateFile('doc.rtf', 'rtf'));
    }

    public function test_validate_file_rejects_missing_files(): void
    {
        $this->assertFalse($this->service->validateFile('nao-existe.txt', 'txt'));
    }

    public function test_validate_file_rejects_files_larger_than_10mb(): void
    {
        Storage::disk('local')->put('grande.txt', str_repeat('a', 11 * 1024 * 1024));

        $this->assertFalse($this->service->validateFile('grande.txt', 'txt'));
    }

    public function test_validate_file_accepts_a_supported_file_within_the_size_limit(): void
    {
        Storage::disk('local')->put('valido.txt', 'conteúdo pequeno');

        $this->assertTrue($this->service->validateFile('valido.txt', 'txt'));
    }
}
