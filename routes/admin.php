<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Middleware\AdminSession;
use App\Models\ChatbotQuestion;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::get('/admin/login', [AuthController::class, 'show'])->name('login');
Route::post('/admin/login', [AuthController::class, 'login']);
Route::post('/admin/logout', [AuthController::class, 'logout'])->middleware('auth:web');
Route::prefix('admin/chatbot')->middleware(['auth:web', AdminSession::class, 'can:access-chatbot-admin'])->group(function () {
    Route::get('/', fn () => view('admin.shell', ['page' => 'dashboard']));
    Route::get('/questions', fn () => view('admin.shell', ['page' => 'questions']));
    Route::get('/faqs', fn () => view('admin.shell', ['page' => 'faqs']));
    Route::get('/questions/{id}', function (int $id) {
        Gate::authorize('view', ChatbotQuestion::findOrFail($id));

        return view('admin.shell', ['page' => 'question-detail', 'resourceId' => $id]);
    })->whereNumber('id');
    Route::post('/password/confirm', [AuthController::class, 'confirm']);
    Route::get('/data/metrics', [QuestionController::class, 'metrics']);
    Route::post('/data/questions/search', [QuestionController::class, 'search']);
    Route::get('/data/questions/{id}', [QuestionController::class, 'show'])->whereNumber('id');
    Route::post('/data/faqs/search', [FaqController::class, 'search']);
    Route::get('/data/faqs/{id}', [FaqController::class, 'show'])->whereNumber('id');
    Route::post('/data/faqs', [FaqController::class, 'create']);
    Route::patch('/data/faqs/{id}', [FaqController::class, 'update'])->whereNumber('id');
    Route::post('/data/faqs/{id}/activation', [FaqController::class, 'activation'])->whereNumber('id');
    Route::delete('/data/faqs/{id}', [FaqController::class, 'delete'])->whereNumber('id');
    Route::get('/faqs/create', function (Request $request) {
        if ($request->has('source_question_id')) {
            $request->validate(['source_question_id' => 'required|integer|min:1']);
            Gate::authorize('view', ChatbotQuestion::findOrFail($request->integer('source_question_id')));
        }

        return view('admin.shell', ['page' => 'faq-create', 'sourceQuestionId' => $request->integer('source_question_id') ?: null]);
    });
    Route::get('/faqs/{id}', function (int $id) {
        Gate::authorize('view', Faq::findOrFail($id));

        return view('admin.shell', ['page' => 'faq-detail', 'resourceId' => $id]);
    })->whereNumber('id');
    Route::get('/faqs/{id}/edit', function (int $id) {
        Gate::authorize('update', Faq::findOrFail($id));

        return view('admin.shell', ['page' => 'faq-edit', 'resourceId' => $id]);
    })->whereNumber('id');
    Route::delete('/data/questions/{id}', [QuestionController::class, 'delete'])->whereNumber('id');
});
