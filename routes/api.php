<?php

use App\Http\Controllers\ChatbotQuestionController;
use App\Http\Middleware\ChatbotOrigin;
use App\Http\Middleware\ChatbotRateLimit;
use Illuminate\Support\Facades\Route;

Route::post('/chatbot/questions', ChatbotQuestionController::class)->middleware([ChatbotOrigin::class, ChatbotRateLimit::class]);
