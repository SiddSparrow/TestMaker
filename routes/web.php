<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TopicController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Matérias, tópicos e tags — geridos inteiramente via modais no dashboard,
    // sem páginas Inertia próprias, então só store/update/destroy existem.
    Route::resource('subjects', SubjectController::class)->only(['store', 'update', 'destroy']);
    Route::resource('topics', TopicController::class)->only(['store', 'update', 'destroy']);
    Route::resource('tags', TagController::class)->only(['store', 'update', 'destroy']);

    // Questões
    Route::resource('questions', QuestionController::class);
    Route::post('/questions/{question}/copy', [QuestionController::class, 'copy'])
        ->name('questions.copy');

    // Provas
    Route::resource('exams', ExamController::class);
    Route::get('/exams/{exam}/questions', [ExamController::class, 'questions'])->name('exams.questions');
    Route::post('/exams/{exam}/toggle-publish', [ExamController::class, 'togglePublish'])->name('exams.toggle-publish');
    Route::post('/exams/{exam}/duplicate', [ExamController::class, 'duplicate'])->name('exams.duplicate');
    Route::get('/exams/{exam}/export-pdf', [ExamController::class, 'exportPdf'])->name('exams.export.pdf');
    Route::get('/exams/{exam}/export-docx', [ExamController::class, 'exportDocx'])->name('exams.export.docx');

    // Documentos
    Route::resource('documents', DocumentController::class)->except(['edit', 'update']);
    Route::post('/documents/{document}/import-questions', [DocumentController::class, 'importQuestions'])->name('documents.import-questions');
    Route::post('/documents/{document}/reprocess', [DocumentController::class, 'reprocess'])->name('documents.reprocess');
});

require __DIR__ . '/auth.php';
