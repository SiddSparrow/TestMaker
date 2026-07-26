<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocumentExtractionJob;
use App\Models\Document;
use App\Models\Question;
use App\Models\QuestionAlternative;
use App\Models\QuestionType;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DocumentController extends Controller
{
    // Mesmo padrão de whitelist do QuestionController — Questões e Provas já
    // ordenavam por coluna, Documentos era a única das três sem isso.
    protected $sortableColumns = ['original_name', 'status', 'created_at'];

    /**
     * Mostra a lista de documentos do usuário
     */
    public function index(Request $request)
    {
        // Antes não havia nenhum filtro nesta tela — com 200 arquivos
        // enviados não havia como achar um documento específico a não ser
        // rolando a lista inteira.
        $filters = $request->only(['search', 'status', 'per_page', 'sort', 'direction']);

        $query = Document::where('user_id', auth()->id());

        if (!empty($filters['search'])) {
            $query->where('original_name', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $sort = $filters['sort'] ?? null;
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if ($sort && in_array($sort, $this->sortableColumns, true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = $filters['per_page'] ?? 15;

        $documents = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
            'filters' => $filters,
        ]);
    }

    /**
     * Mostra o formulário de upload
     */
    public function create()
    {
        return Inertia::render('Documents/Create');
    }

    /**
     * Faz upload e dispara processamento
     */
    public function store(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,docx,txt|max:10240', // 10MB
        ], [
            'document.required' => 'Por favor, selecione um arquivo',
            'document.mimes' => 'Apenas arquivos PDF, DOCX ou TXT são permitidos',
            'document.max' => 'O arquivo não pode ser maior que 10MB',
        ]);

        $file = $request->file('document');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $fileSize = $file->getSize();

        // Salvar arquivo
        $path = $file->store('documents', 'local');

        // Criar registro no banco
        $document = Document::create([
            'user_id' => auth()->id(),
            'original_name' => $originalName,
            'file_path' => $path,
            'file_type' => $extension,
            'file_size' => $fileSize,
            'status' => 'pending',
        ]);

        // Disparar job assíncrono
        ProcessDocumentExtractionJob::dispatch($document);

        return redirect()->route('documents.index')
            ->with('success', 'Documento enviado com sucesso! O processamento está sendo feito em segundo plano e levará alguns minutos.');
    }

    /**
     * Mostra o documento e resultado da extração
     */
    public function show(Document $document)
    {
        // Verificar permissão
        if ($document->user_id !== auth()->id()) {
            abort(403, 'Você não tem permissão para visualizar este documento');
        }

        // Carregar dados necessários para importação
        $subjects = Subject::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        $topics = Topic::where('user_id', auth()->id())
            ->with('subject')
            ->orderBy('name')
            ->get();

        $questionTypes = QuestionType::all();

        return Inertia::render('Documents/Show', [
            'document' => $document,
            'subjects' => $subjects,
            'topics' => $topics,
            'questionTypes' => $questionTypes,
        ]);
    }

    /**
     * Importa questões validadas para o banco
     */
    public function importQuestions(Request $request, Document $document)
    {
        // Verificar permissão
        if ($document->user_id !== auth()->id()) {
            abort(403, 'Você não tem permissão para importar questões deste documento');
        }

        // Verificar se o documento foi processado
        if ($document->status !== 'completed') {
            return back()->with('error', 'Documento ainda não foi processado ou falhou');
        }

        $request->validate([
            'questions' => 'required|array|min:1',
            'questions.*.statement' => 'required|string|min:10',
            'questions.*.type' => 'required|string',
            'questions.*.subject_id' => ['required', Rule::exists('subjects', 'id')->where('user_id', auth()->id())],
            'questions.*.topic_id' => ['nullable', Rule::exists('topics', 'id')->where('user_id', auth()->id())],
            'questions.*.difficulty_level' => 'required|in:easy,medium,hard',
            'questions.*.points' => 'nullable|numeric|min:0.5|max:10',
            'questions.*.explanation' => 'nullable|string',
            'questions.*.alternatives' => 'array',
            'questions.*.alternatives.*.content' => 'required|string',
            'questions.*.alternatives.*.is_correct' => 'required|boolean',
        ], [
            'questions.*.subject_id.required' => 'Selecione uma matéria para cada questão antes de importar.',
            'questions.*.subject_id.exists' => 'A matéria selecionada em uma das questões não é válida.',
            'questions.*.topic_id.exists' => 'O tópico selecionado em uma das questões não é válido.',
            'questions.*.statement.required' => 'O enunciado de uma das questões está vazio.',
            'questions.*.statement.min' => 'O enunciado de uma das questões é curto demais.',
            'questions.*.difficulty_level.required' => 'Selecione a dificuldade de cada questão antes de importar.',
            'questions.*.alternatives.*.content.required' => 'Preencha o conteúdo de todas as alternativas antes de importar.',
        ]);

        $importedCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($request->questions as $index => $questionData) {
                // Verificar tipo de questão
                $questionType = QuestionType::where('name', $questionData['type'])->first();
                if (!$questionType) {
                    // Tentar mapear tipos
                    $typeMap = [
                        'multiple_choice' => 'Múltipla Escolha',
                        'true_false' => 'Verdadeiro ou Falso',
                        'essay' => 'Dissertativa',
                    ];
                    $typeName = $typeMap[$questionData['type']] ?? 'Múltipla Escolha';
                    $questionType = QuestionType::where('name', $typeName)->first();
                }

                if (!$questionType) {
                    $errors[] = 'Questão ' . ($index + 1) . ': tipo de questão inválido';
                    continue;
                }

                // Criar questão
                $question = Question::create([
                    'user_id' => auth()->id(),
                    'document_id' => $document->id,
                    'question_type_id' => $questionType->id,
                    'subject_id' => $questionData['subject_id'],
                    'topic_id' => $questionData['topic_id'] ?? null,
                    'statement' => $questionData['statement'],
                    'explanation' => $questionData['explanation'] ?? null,
                    'difficulty_level' => $questionData['difficulty_level'],
                    'points' => $questionData['points'] ?? 1.0,
                    'is_active' => true,
                ]);

                // Criar alternativas se houver
                if (!empty($questionData['alternatives'])) {
                    foreach ($questionData['alternatives'] as $altIndex => $alternative) {
                        QuestionAlternative::create([
                            'question_id' => $question->id,
                            'content' => $alternative['content'],
                            'is_correct' => $alternative['is_correct'],
                            'order' => $altIndex + 1,
                        ]);
                    }
                }

                $importedCount++;
            }

            DB::commit();

            // Sem isto, os cards de estatística (QuestionController::getCachedStats)
            // continuam com os números de antes da importação por até 30 min —
            // ver o mesmo padrão em QuestionController::clearQuestionCache().
            $userId = auth()->id();
            Cache::forget('questions_stats_' . $userId);
            Cache::forget('questions_create_data_' . $userId);
            Cache::forget('questions_edit_data_' . $userId);

            // O status permanece 'completed' (fora do enum não existe
            // 'imported'); a importação é registrada em imported_at.
            $document->update(['imported_at' => now()]);

            $message = "{$importedCount} questões importadas com sucesso!";
            if (!empty($errors)) {
                $message .= ' Alguns itens tiveram problemas: ' . implode(', ', $errors);
            }

            return redirect()->route('questions.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Erro ao importar questões: ' . $e->getMessage());
        }
    }

    /**
     * Remove documento
     */
    public function destroy(Document $document)
    {
        // Verificar permissão
        if ($document->user_id !== auth()->id()) {
            abort(403, 'Você não tem permissão para excluir este documento');
        }

        // Deletar arquivo do storage
        if (Storage::exists($document->file_path)) {
            Storage::delete($document->file_path);
        }

        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', 'Documento excluído com sucesso');
    }

    /**
     * Reprocessar documento que falhou
     */
    public function reprocess(Document $document)
    {
        // Verificar permissão
        if ($document->user_id !== auth()->id()) {
            abort(403, 'Você não tem permissão para reprocessar este documento');
        }

        // Limpar resultado anterior
        $document->update([
            'status' => 'pending',
            'extraction_result' => null,
            'error_message' => null,
        ]);

        // Disparar job novamente
        ProcessDocumentExtractionJob::dispatch($document);

        return back()->with('success', 'Documento reenviado para processamento');
    }
}
