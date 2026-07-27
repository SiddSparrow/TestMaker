<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TopicController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $topics = Topic::withCount('questions')
            ->with('subject:id,name,color')
            ->where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        $subjects = Subject::select('id', 'name', 'color')
            ->where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        return Inertia::render('Topics/Index', [
            'topics' => $topics,
            'subjects' => $subjects,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => ['required', Rule::exists('subjects', 'id')->where('user_id', auth()->id())],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $topic = Topic::create(array_merge($validated, ['user_id' => auth()->id()]));

        $this->clearQuestionFormCache();

        // Ver comentário equivalente em SubjectController@store.
        if ($request->wantsJson()) {
            return response()->json(['topic' => $topic]);
        }

        return back()->with('success', 'Tópico criado com sucesso!');
    }

    public function update(Request $request, Topic $topic)
    {
        $this->authorize('update', $topic);

        $validated = $request->validate([
            'subject_id' => ['required', Rule::exists('subjects', 'id')->where('user_id', auth()->id())],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $topic->update($validated);

        $this->clearQuestionFormCache();

        return back()->with('success', 'Tópico atualizado com sucesso!');
    }

    public function destroy(Topic $topic)
    {
        $this->authorize('delete', $topic);

        $topic->delete();

        $this->clearQuestionFormCache();

        return back()->with('success', 'Tópico excluído com sucesso!');
    }

    /**
     * Limpa os caches (30 min, ver QuestionController) que embutem a lista
     * de tópicos — sem isto, um tópico criado/editado/excluído aqui não
     * aparece no <select> nem no filtro do formulário de questão até expirar.
     */
    private function clearQuestionFormCache(): void
    {
        $userId = auth()->id();

        Cache::forget('topics_all' . $userId);
        Cache::forget('questions_create_data_' . $userId);
        Cache::forget('questions_edit_data_' . $userId);
    }
}
