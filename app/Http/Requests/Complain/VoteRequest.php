<?php

namespace App\Http\Requests\Complain;

use Illuminate\Foundation\Http\FormRequest;

class VoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|exists:complains,id',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Complaint ID is required',
            'id.exists' => 'Complaint not found',
        ];
    }
}