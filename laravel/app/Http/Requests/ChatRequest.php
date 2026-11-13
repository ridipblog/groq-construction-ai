<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'messages' => ['required', 'array', 'min:1'],
            'messages.*.role' => ['required', 'string', 'in:user,assistant,system'],
            'messages.*.content' => ['required', 'string'],
            'context' => ['sometimes', 'nullable', 'string'],
            'active_estimate' => ['sometimes', 'nullable', 'array'],
        ];
    }

    /**
     * Custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'messages.required' => 'The messages array is required.',
            'messages.min' => 'At least one message is required.',
            'messages.*.role.in' => 'Each message role must be user, assistant, or system.',
            'messages.*.content.required' => 'Each message must have content.',
            'active_estimate.array' => 'The active estimate must be a valid structured array.',
        ];
    }
}
