<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /**
     * Matérias são geridas inteiramente pelo modal do dashboard
     * (SubjectsModal.vue) — não há páginas Inertia de index/create/show/edit.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);

        Subject::create(array_merge($validated, ['user_id' => auth()->id()]));

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

        return back()->with('success', 'Matéria excluída com sucesso!');
    }
}
