<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TopicController extends Controller
{
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

        // Ver comentário equivalente em SubjectController@store.
        if ($request->wantsJson()) {
            return response()->json(['topic' => $topic]);
        }

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
