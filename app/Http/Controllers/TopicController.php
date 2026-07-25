<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TopicController extends Controller
{
    /**
     * Tópicos são geridos inteiramente pelo modal do dashboard
     * (TopicsModal.vue) — não há páginas Inertia de index/create/show/edit.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => ['required', Rule::exists('subjects', 'id')->where('user_id', auth()->id())],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Topic::create(array_merge($validated, ['user_id' => auth()->id()]));

        return back()->with('success', 'Tópico criado com sucesso!');
    }

    public function update(Request $request, Topic $topic)
    {
        $validated = $request->validate([
            'subject_id' => ['required', Rule::exists('subjects', 'id')->where('user_id', auth()->id())],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $topic->update($validated);

        return back()->with('success', 'Tópico atualizado com sucesso!');
    }

    public function destroy(Topic $topic)
    {
        $topic->delete();

        return back()->with('success', 'Tópico excluído com sucesso!');
    }
}
