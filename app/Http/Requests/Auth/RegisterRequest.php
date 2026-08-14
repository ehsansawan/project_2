<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => 'regex:/^09\d{8}$/',//|unique:users,phone_number
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
//            'national_id' => [
//                'nullable',
//                'string',
//                'regex:/^[0-9]{11}$/',
//                'unique:users,national_id',
//            ],
            'birth_date' => 'required|date',
            'fcm_token' => 'string|nullable',
          //  'privacy_policy_accepted'=>'required|accepted',
           // 'terms_of_service_accepted'=>'required|accepted',

        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required',
            'first_name.max' => 'First name may not be greater than 255 characters',
            'last_name.required' => 'Last name is required',
            'last_name.max' => 'Last name may not be greater than 255 characters',
            'email.required' => 'Email is required',
            'email.email' => 'Please enter a valid email address',
            'email.max' => 'Email may not be greater than 255 characters',
            'email.unique' => 'This email is already registered',
            'phone.regex' => 'Phone number must be 09XXXXXXXX',
            'password.required' => 'Password is required',
            'password.confirmed' => 'Password confirmation does not match',
            'password.uncompromised' => 'This password has been compromised',
            'birth_date.required' => 'Birth date is required',
            'birth_date.date' => 'Please enter a valid birth date',
        ];
    }
}
