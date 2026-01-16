<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Tag;
use App\Models\Topic;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $userId = Auth::id();

        $stats = [
            'total_questions' => Question::where('user_id', $userId)->count(),
            'total_exams' => Exam::where('user_id', $userId)->count(),
            'total_subjects' => Subject::where('user_id', $userId)->count(),
            'total_documents' => Document::where('user_id', $userId)
                ->where('status', 'completed')
                ->count(),
            'total_topics' => Topic::where('user_id', $userId)->count(),
            'total_tags' => Tag::where('user_id', $userId)->count(),
        ];

        $recentQuestions = Question::with(['subject', 'topic'])
            ->where('user_id', $userId)
            ->whereHas('subject')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($question) {
                return [
                    'id' => $question->id,
                    'statement' => str($question->statement)->limit(100),
                    'subject' => [
                        'name' => $question->subject->name,
                        'color' => $question->subject->color,
                    ],
                    'topic' => $question->topic?->name,
                    'difficulty_level' => $question->difficulty_level,
                    'points' => $question->points,
                    'created_at' => $question->created_at->diffForHumans(),
                ];
            });

        $recentExams = Exam::withCount('questions')
            ->where('user_id', $userId)
            ->latest()
            ->limit(4)
            ->get()
            ->map(function ($exam) {
                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'description' => $exam->description,
                    'total_points' => $exam->total_points,
                    'questions_count' => $exam->questions_count,
                    'created_at' => $exam->created_at->format('d/m/Y'),
                ];
            });

        // CORREÇÃO: Especificar qual tabela tem o user_id
        $mostUsedSubjects = Question::where('questions.user_id', $userId) // Especificar tabela
            ->join('subjects', 'questions.subject_id', '=', 'subjects.id')
            ->select(
                'subjects.id',
                'subjects.name',
                'subjects.color',
                DB::raw('COUNT(questions.id) as question_count')
            )
            ->groupBy('subjects.id', 'subjects.name', 'subjects.color')
            ->orderByDesc('question_count')
            ->limit(5)
            ->get()
            ->map(function ($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'color' => $subject->color,
                    'question_count' => $subject->question_count,
                ];
            });
        $subjects = Subject::withCount('topics')->where('user_id', $userId)->get();
        $tags = Tag::withCount('questions')->where('user_id', $userId)->get();
        $topics = Topic::withCount('questions')->where('user_id', $userId)->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recent_questions' => $recentQuestions,
            'recent_exams' => $recentExams,
            'most_used_subjects' => $mostUsedSubjects,
            'subjects' => $subjects,
            'tags' => $tags,
            'topics' => $topics,
        ]);
    }
}
