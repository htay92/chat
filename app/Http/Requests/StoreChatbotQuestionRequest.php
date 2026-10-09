<?php

namespace App\Http\Requests;

use App\Chatbot\FaqNormalizer;
use App\Chatbot\SessionIdentity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreChatbotQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function validationData(): array
    {
        try {
            $data = json_decode($this->attributes->get('chatbot_body', ''), false, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages(['request' => ['Invalid JSON object.']]);
        }
        if (! $data instanceof \stdClass) {
            throw ValidationException::withMessages(['request' => ['Expected a JSON object.']]);
        }
        $data = (array) $data;
        if (array_diff(array_keys($data), ['question', 'session_id', 'request_id'])) {
            throw ValidationException::withMessages(['request' => ['Unexpected request fields.']]);
        }

        return $data;
    }

    public function rules(): array
    {
        return [
            'question' => ['bail', 'required', 'string', function ($attribute, $value, $fail) {
                try {
                    app(FaqNormalizer::class)->question($value);
                } catch (\InvalidArgumentException) {
                    $fail('Use 1–1,000 Unicode characters and nonblank text.');
                }
            }],
            'session_id' => ['bail', 'required', 'string', fn ($attribute, $value, $fail) => SessionIdentity::valid($value) ? null : $fail('Use a canonical UUID v4.')],
            'request_id' => ['bail', 'required', 'string', fn ($attribute, $value, $fail) => SessionIdentity::valid($value) ? null : $fail('Use a canonical UUID v4.')],
        ];
    }

    public function messages(): array
    {
        return ['required' => 'This field is required.', 'string' => 'This field must be text.'];
    }
}
