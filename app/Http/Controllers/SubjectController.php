<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::withCount(['topics', 'questions'])
            ->where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        return Inertia::render('Subjects/Index', [
            'subjects' => $subjects,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);

        $subject = Subject::create(array_merge($validated, ['user_id' => auth()->id()]));

        $this->clearQuestionFormCache();

        // Criação inline no formulário de questão (QuestionFormFields.vue)
        // chama esta rota via axios esperando o registro de volta, sem sair
        // da tela — não é um visit Inertia, então back() não serviria.
        if ($request->wantsJson()) {
            return response()->json(['subject' => $subject]);
        }

        return back()->with('success', 'Matéria criada com sucesso!');
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);

        $subject->update($validated);

        $this->clearQuestionFormCache();

        return back()->with('success', 'Matéria atualizada com sucesso!');
    }

    public function destroy(Subject $subject)
    {
        if ($subject->questions()->exists()) {
            return back()->with(
                'error',
                'Não é possível excluir uma matéria que possui questões vinculadas. Mova ou exclua as questões primeiro.'
            );
        }

        $subject->delete();

        $this->clearQuestionFormCache();

        return back()->with('success', 'Matéria excluída com sucesso!');
    }

    /**
     * Limpa os caches (30 min, ver QuestionController) que embutem a lista
     * de matérias — sem isto, uma matéria criada/editada/excluída aqui não
     * aparece no <select> nem no filtro do formulário de questão até expirar.
     */
    private function clearQuestionFormCache(): void
    {
        $userId = auth()->id();

        Cache::forget('subjects_all' . $userId);
        Cache::forget('questions_create_data_' . $userId);
        Cache::forget('questions_edit_data_' . $userId);
    }
}
