<?php

namespace App\Http\Requests\Queue;

use Illuminate\Foundation\Http\FormRequest;

class AddManualVisitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_name' => 'required|string|min:2|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'user_name.required' => 'Visitor name is required',
            'user_name.min' => 'Name must be at least 2 characters',
            'user_name.max' => 'Name may not be greater than 50 characters',
        ];
    }
}