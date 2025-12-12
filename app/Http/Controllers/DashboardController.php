<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Question;
use App\Models\Exam;
use App\Models\Subject;
use App\Models\Document;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'total_questions' => Question::count(),
            'total_exams' => Exam::count(),
            'total_subjects' => Subject::count(),
            'total_documents' => Document::where('status', 'completed')->count(),
        ];

        $recentQuestions = Question::with(['subject', 'topic'])
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

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recent_questions' => $recentQuestions,
            'recent_exams' => $recentExams,
        ]);
    }
}