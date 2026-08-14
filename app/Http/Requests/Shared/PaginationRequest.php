<?php

namespace App\Http\Requests\Shared;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PaginationRequest extends FormRequest
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
            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:100',
        ];
    }

    protected function paginationMessages(): array
    {
        return [
            'page.integer' => 'Page must be a number',
            'page.min' => 'Page must be at least 1',
            'page_size.integer' => 'Page size must be a number',
            'page_size.min' => 'Page size must be at least 1',
            'page_size.max' => 'Page size may not exceed 100',
        ];
    }
}
