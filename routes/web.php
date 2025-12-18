<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TopicController;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;
/*Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});*/

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/logout', function () {
        Auth::logout();
        event(new Logout('web', Auth::user()));
        return redirect('/login');
    })->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    //rotas de perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Resources
    Route::resource('questions', QuestionController::class);
    Route::resource('exams', ExamController::class);
    Route::resource('documents', DocumentController::class);
    Route::resource('subjects', SubjectController::class);
    Route::resource('topics', TopicController::class);
    Route::post('/questions/{question}/copy', [QuestionController::class, 'copy'])
    ->name('questions.copy');
    
    // Rotas customizadas
    Route::post('/documents/{document}/process', [DocumentController::class, 'process'])->name('documents.process');
    Route::get('/exams/{exam}/preview', [ExamController::class, 'preview'])->name('exams.preview');
    Route::get('/exams/{exam}/export', [ExamController::class, 'export'])->name('exams.export');

    // Rotas de Exportação
    Route::get('exams/{exam}/export-pdf', [ExamController::class, 'exportPdf'])->name('exams.export.pdf');
    Route::get('exams/{exam}/export-docx', [ExamController::class, 'exportDocx'])->name('exams.export.docx');
});

require __DIR__.'/auth.php';

require __DIR__.'/auth.php';
