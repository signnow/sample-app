<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmbeddedInviteRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fields' => 'required|array',
            'fields.first_name' => 'required|string|max:40',
            'fields.last_name' => 'required|string|max:40',
            'fields.comment' => 'nullable|string|max:64',
        ];
    }

    public function messages(): array
    {
        return [
            'fields.required' => 'The fields array is required.',
            'fields.first_name.required' => 'The fields.first_name attribute is required.',
            'fields.first_name.string' => 'The fields.first_name attribute must be a string.',
            'fields.first_name.max' => 'The fields.first_name attribute must not exceed 40 characters.',
            'fields.last_name.required' => 'The fields.last_name attribute is required.',
            'fields.last_name.string' => 'The fields.last_name attribute must be a string.',
            'fields.last_name.max' => 'The fields.last_name attribute must not exceed 40 characters.',
            'fields.comment.string' => 'The fields.comment attribute must be a string.',
            'fields.comment.max' => 'The fields.comment attribute must not exceed 64 characters.',
        ];
    }
}
