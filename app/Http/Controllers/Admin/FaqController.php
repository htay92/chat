<?php

namespace App\Http\Controllers\Admin;

use App\Chatbot\Admin\AdminInput;
use App\Chatbot\FaqService;
use App\Models\ChatbotQuestion;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class FaqController
{
    public static function dto(Faq $faq): array
    {
        return ['id' => $faq->id, 'question' => $faq->question, 'answer' => $faq->answer, 'is_active' => $faq->is_active, 'created_at' => $faq->created_at->toISOString(), 'updated_at' => $faq->updated_at->toISOString(), 'revision' => app(FaqService::class)->revision($faq)];
    }

    public function show(int $id)
    {
        $faq = Faq::findOrFail($id);
        Gate::authorize('view', $faq);

        return response()->json(['data' => self::dto($faq)]);
    }

    public function search(Request $request)
    {
        Gate::authorize('viewAny', Faq::class);

        return QuestionController::listing($request, Faq::query(), true);
    }

    public function create(Request $request)
    {
        Gate::authorize('create', Faq::class);
        $data = AdminInput::fields($request, ['question' => 'required|string', 'answer' => 'required|string', 'source_question_id' => 'sometimes|integer|min:1']);
        if (isset($data['source_question_id'])) {
            Gate::authorize('view', ChatbotQuestion::findOrFail($data['source_question_id']));
        }
        $faq = app(FaqService::class)->create($data['question'], $data['answer']);
        self::audit($request, 'faq-create', $faq->id);

        return response()->json(['data' => self::dto($faq)], 201);
    }

    public function update(Request $request, int $id)
    {
        Gate::authorize('update', Faq::findOrFail($id));
        $data = AdminInput::fields($request, ['question' => 'required|string', 'answer' => 'required|string', 'revision' => 'required|string|size:64|regex:/\A[a-f0-9]{64}\z/']);
        $faq = app(FaqService::class)->update($id, $data['revision'], $data['question'], $data['answer']);
        self::audit($request, 'faq-update', $id);

        return response()->json(['data' => self::dto($faq)]);
    }

    public function activation(Request $request, int $id)
    {
        Gate::authorize('update', Faq::findOrFail($id));
        $data = AdminInput::fields($request, ['revision' => 'required|string|size:64|regex:/\A[a-f0-9]{64}\z/', 'is_active' => 'required|boolean', 'confirmed' => 'required|accepted']);
        if (! is_bool($data['is_active']) || $data['confirmed'] !== true) {
            throw new \InvalidArgumentException;
        }
        $faq = app(FaqService::class)->activate($id, $data['revision'], $data['is_active']);
        self::audit($request, $data['is_active'] ? 'faq-activate' : 'faq-deactivate', $id);

        return response()->json(['data' => self::dto($faq)]);
    }

    public static function audit(Request $request, string $action, int $id): void
    {
        Log::channel('chatbot_audit')->info('chatbot_admin', ['actor_id' => $request->user()->id, 'action' => $action, 'resource_id' => $id, 'outcome' => 'completed', 'time' => now('UTC')->toISOString()]);
    }

    public function delete(Request $request, int $id)
    {
        Gate::authorize('delete', Faq::findOrFail($id));
        AdminInput::recentPassword($request);
        $data = AdminInput::fields($request, ['revision' => 'required|string|size:64|regex:/\A[a-f0-9]{64}\z/', 'confirmed' => 'required|accepted']);
        if ($data['confirmed'] !== true) {
            throw new \InvalidArgumentException;
        }
        app(FaqService::class)->delete($id, $data['revision']);
        self::audit($request, 'faq-delete', $id);

        return response()->noContent();
    }
}
