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

    // Matérias, tópicos e tags — cada um com sua própria tela de gestão
    // (Subjects/Index, Topics/Index, Tags/Index); create/show/edit não
    // existem como páginas separadas porque o form de criar/editar já vive
    // na própria index, então só esses 4 verbos são registrados.
    Route::resource('subjects', SubjectController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('topics', TopicController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('tags', TagController::class)->only(['index', 'store', 'update', 'destroy']);

    // Questões — bulk-status precisa vir ANTES do resource: como é uma
    // rota literal (/questions/bulk-status) do mesmo verbo PATCH usado por
    // questions.update (/questions/{question}), se viesse depois o Laravel
    // casaria "bulk-status" com {question} e nunca chegaria aqui.
    Route::patch('/questions/bulk-status', [QuestionController::class, 'bulkStatus'])
        ->name('questions.bulk-status');
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
