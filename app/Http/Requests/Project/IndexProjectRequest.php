<?php

namespace App\Http\Requests\Project;

use App\Enums\ProjectStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class IndexProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('statuses') && is_string($this->input('statuses'))) {
            $this->merge([
                'statuses' => array_filter(array_map('trim', explode(',', $this->input('statuses')))),
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
            'statuses' => ['nullable', 'array'],
            'statuses.*' => ['string', new Enum(ProjectStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'statuses.array' => 'The statuses parameter must be an array of status values.',
            'statuses.*.enum' => 'The status :input is invalid.',
        ];
    }
}