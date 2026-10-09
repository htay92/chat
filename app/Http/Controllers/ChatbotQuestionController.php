<?php

namespace App\Http\Controllers;

use App\Chatbot\QuestionAnswerService;
use App\Http\Requests\StoreChatbotQuestionRequest;

class ChatbotQuestionController extends Controller
{
    public function __invoke(StoreChatbotQuestionRequest $request, QuestionAnswerService $service)
    {
        $data = $request->validated();
        $reply = $service->submit($data['question'], $data['session_id'], $data['request_id']);

        return response()->json(['data' => $reply], $reply['replayed'] ? 200 : 201);
    }
}
