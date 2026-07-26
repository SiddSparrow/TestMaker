<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::withCount('questions')
            ->where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        return Inertia::render('Tags/Index', [
            'tags' => $tags,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tags', 'name')->where('user_id', auth()->id()),
            ],
            'slug' => 'nullable|string|max:255',
        ]);

        Tag::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
        ]);

        $this->clearQuestionFormCache();

        return back()->with('success', 'Etiqueta criada com sucesso!');
    }

    public function update(Request $request, Tag $tag)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tags', 'name')->where('user_id', auth()->id())->ignore($tag->id),
            ],
            'slug' => 'nullable|string|max:255',
        ]);

        $tag->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
        ]);

        $this->clearQuestionFormCache();

        return back()->with('success', 'Etiqueta atualizada com sucesso!');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        $this->clearQuestionFormCache();

        return back()->with('success', 'Etiqueta excluída com sucesso!');
    }

    /**
     * Limpa os caches (30 min, ver QuestionController) que embutem a lista
     * de etiquetas — sem isto, uma etiqueta criada/editada/excluída aqui não
     * aparece no formulário de questão até expirar.
     */
    private function clearQuestionFormCache(): void
    {
        $userId = auth()->id();

        Cache::forget('tags_all' . $userId);
        Cache::forget('questions_create_data_' . $userId);
        Cache::forget('questions_edit_data_' . $userId);
    }
}
