<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DeleteUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'The user id is required.',
            'id.integer' => 'The user id must be an integer.',
            'id.exists' => 'The user does not exist.',
        ];
    }
}