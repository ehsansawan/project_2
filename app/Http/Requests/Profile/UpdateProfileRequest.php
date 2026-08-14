<?php

namespace App\Http\Requests\Profile;

use App\Enums\AccountStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
        $canEditIdentity = auth()->user()->account_status === AccountStatus::Visitor->value;

        return [
            'birth_date' => [Rule::when($canEditIdentity, 'date', 'prohibited')],
            'first_name' => [Rule::when($canEditIdentity, 'string', 'prohibited'), 'max:255'],
            'last_name' => [Rule::when($canEditIdentity, 'string', 'prohibited'), 'max:255'],
            'image'=>'nullable|image|mimes:jpeg,jpg,png,webp|max:4096'
        ];
    }

    public function messages(): array
    {
        return [
            'birth_date.date' => 'Please enter a valid birth date',
            'birth_date.prohibited' => 'Birth date cannot be changed',
            'first_name.string' => 'First name must be a string',
            'first_name.max' => 'First name may not be greater than 255 characters',
            'first_name.prohibited' => 'First name cannot be changed',
            'last_name.string' => 'Last name must be a string',
            'last_name.max' => 'Last name may not be greater than 255 characters',
            'last_name.prohibited' => 'Last name cannot be changed',
            'image.image' => 'The profile image must be an image',
            'image.mimes' => 'The profile image must be a file of type: jpeg, jpg, png, webp',
            'image.max' => 'The profile image may not be greater than 4MB',
        ];
    }
}
