<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            'code'=>'required|string|exists:reset_code_passwords,code',
            'password'=>['required','string','confirmed', Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()]
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Reset code is required',
            'code.exists' => 'The reset code is invalid or expired',
            'password.required' => 'Password is required',
            'password.confirmed' => 'Password confirmation does not match',
            'password.uncompromised' => 'This password has been compromised',
        ];
    }
}
