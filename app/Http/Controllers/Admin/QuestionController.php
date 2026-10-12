<?php

namespace App\Http\Controllers\Admin;

use App\Chatbot\Admin\AdminInput;
use App\Chatbot\Admin\RestorationSink;
use App\Chatbot\FaqNormalizer;
use App\Models\ChatbotQuestion;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class QuestionController
{
    public function metrics()
    {
        Gate::authorize('viewAny', ChatbotQuestion::class);
        $questions = ChatbotQuestion::selectRaw('COUNT(*) AS total, COALESCE(SUM(status = ?),0) AS unanswered', ['unanswered'])->first();
        $faqs = Faq::selectRaw('COALESCE(SUM(is_active = 1),0) AS active, COALESCE(SUM(is_active = 0),0) AS inactive')->first();

        return response()->json(['data' => ['total_questions' => (int) $questions->total, 'originally_unanswered' => (int) $questions->unanswered, 'active_faqs' => (int) $faqs->active, 'inactive_faqs' => (int) $faqs->inactive]]);
    }

    public function show(int $id)
    {
        $question = ChatbotQuestion::findOrFail($id);
        Gate::authorize('view', $question);

        return response()->json(['data' => self::dto($question)]);
    }

    public function search(Request $request)
    {
        Gate::authorize('viewAny', ChatbotQuestion::class);

        return self::listing($request, ChatbotQuestion::query(), false);
    }

    public static function dto(ChatbotQuestion $q): array
    {
        return ['id' => $q->id, 'question' => $q->question, 'status' => $q->status, 'created_at' => $q->created_at->toISOString(), 'faq_id' => $q->faq_id];
    }

    public static function listing(Request $request, $query, bool $faq)
    {
        $rules = ['search' => 'sometimes|string', 'per_page' => 'sometimes|integer|in:25,50', 'cursor' => 'sometimes|nullable|string|max:2048'];
        $rules[$faq ? 'active' : 'status'] = $faq ? 'sometimes|in:active,inactive' : 'sometimes|in:answered,unanswered';
        $data = AdminInput::fields($request, $rules);
        if (isset($data['per_page']) && ! is_int($data['per_page'])) {
            throw ValidationException::withMessages(['per_page' => 'Choose 25 or 50 rows.']);
        }
        $search = isset($data['search']) ? app(FaqNormalizer::class)->trim($data['search']) : '';
        if (mb_strlen($search, 'UTF-8') > 200) {
            throw ValidationException::withMessages(['search' => 'Search must not exceed 200 characters.']);
        }
        if ($search !== '') {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->whereRaw("question COLLATE utf8mb4_0900_as_ci LIKE ? ESCAPE '!'", [$pattern]);
        }
        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (isset($data['active'])) {
            $query->where('is_active', $data['active'] === 'active');
        }
        $cursor = null;
        if (isset($data['cursor']) && $data['cursor'] !== '') {
            try {
                $cursor = Cursor::fromEncoded($data['cursor']);
                $params = $cursor?->toArray();
                if (! is_array($params) || array_diff(array_keys($params), ['id', 'created_at', '_pointsToNextItems']) || count($params) !== 3
                    || ! is_int($params['id']) || $params['id'] < 1 || ! is_bool($params['_pointsToNextItems'])
                    || ! is_string($params['created_at']) || ! preg_match('/\A\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\z/', $params['created_at'])) {
                    throw new \InvalidArgumentException;
                }
            } catch (\Throwable) {
                throw ValidationException::withMessages(['cursor' => 'Invalid page cursor.']);
            }
        }
        $direction = $faq ? 'desc' : 'asc';
        $columns = $faq ? ['id', 'question', 'answer', 'is_active', 'created_at', 'updated_at'] : ['id', 'question', 'status', 'created_at', 'faq_id'];
        $page = $query->orderBy('created_at', $direction)->orderBy('id', $direction)->cursorPaginate($data['per_page'] ?? 25, $columns, 'cursor', $cursor);
        $items = $page->getCollection()->map($faq ? [FaqController::class, 'dto'] : [self::class, 'dto']);

        return response()->json(['data' => $items, 'next_cursor' => $page->nextCursor()?->encode(), 'previous_cursor' => $page->previousCursor()?->encode()]);
    }

    public function delete(Request $request, int $id)
    {
        Gate::authorize('delete', ChatbotQuestion::findOrFail($id));
        AdminInput::recentPassword($request);
        $data = AdminInput::fields($request, ['confirmed' => 'required|accepted']);
        if ($data['confirmed'] !== true) {
            throw new \InvalidArgumentException;
        }
        DB::transaction(function () use ($id) {
            $q = ChatbotQuestion::lockForUpdate()->findOrFail($id);
            try {
                app(RestorationSink::class)->record(['question_id' => $q->id, 'created_at' => $q->created_at->toISOString(), 'deleted_at' => now('UTC')->toISOString()]);
            } catch (\Throwable) {
                abort(503);
            }
            $q->delete();
        });
        FaqController::audit($request, 'question-delete', $id);

        return response()->noContent();
    }
}
