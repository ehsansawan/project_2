<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('roles') && is_string($this->input('roles'))) {
            $this->merge([
                'roles' => array_filter(array_map('trim', explode(',', $this->input('roles')))),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'roles.array' => 'The roles parameter must be an array of role names.',
            'roles.*.exists' => 'The role :input does not exist.',
        ];
    }
}