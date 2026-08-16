<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CreateAdminRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'phone' => ['required', 'string', 'regex:/^09\d{8}$/', 'unique:users,phone'],
            'national_id' => ['nullable', 'string', 'max:15', 'unique:users,national_id'],
            'birth_date' => ['nullable', 'date', 'before:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'The phone number is required.',
            'phone.regex' => 'The phone number must start with 09 and be 10 digits.',
            'phone.unique' => 'This phone number is already registered.',
            'email.unique' => 'This email is already registered.',
            'national_id.unique' => 'This national ID is already registered.',
            'birth_date.before' => 'The birth date must be a date before today.',
        ];
    }
}