<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
            'phone' => ['sometimes', 'string', 'regex:/^09\d{8}$/'
                , Rule::unique('users', 'phone')->ignore(auth('api')->id())],
            'fcm_token' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone number must start with 09 and be 10 digits.',
            'phone.unique' => 'This phone number is already registered.',
        ];
    }
}
