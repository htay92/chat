<?php

namespace App\Chatbot\Admin;

use App\Chatbot\FaqNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AdminInput
{
    public static function fields(Request $request, array $rules): array
    {
        $data = $request->except('_token');
        if (array_diff(array_keys($data), array_keys($rules))) {
            throw ValidationException::withMessages(['form' => 'Unexpected fields.']);
        }
        $validated = Validator::make($data, $rules)->validate();
        foreach (['question', 'answer'] as $field) {
            if (isset($validated[$field])) {
                try {
                    $validated[$field] = app(FaqNormalizer::class)->{$field}($validated[$field]);
                } catch (\InvalidArgumentException) {
                    throw ValidationException::withMessages([$field => $field === 'question'
                        ? 'Enter a question of 1–1,000 characters.' : 'Enter an answer of 1–4,000 characters.']);
                }
            }
        }
        if (isset($validated['source_question_id']) && ! is_int($validated['source_question_id'])) {
            throw ValidationException::withMessages(['source_question_id' => 'Invalid source question.']);
        }

        return $validated;
    }

    public static function recentPassword(Request $request): void
    {
        $time = $request->session()->get('admin_password_confirmed', 0);
        abort_if(! is_int($time) || $time > now()->timestamp || now()->timestamp - $time >= 900, 428);
    }
}
