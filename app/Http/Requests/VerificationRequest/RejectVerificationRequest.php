<?php

namespace App\Http\Requests\VerificationRequest;

use App\Enums\RejectionReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RejectVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:verification_requests,id',
            ],

            'reason' => ['required', 'array', 'min:1'],
            'reason.*' => ['required', new Enum(RejectionReason::class)],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Verification request ID is required',
            'id.exists' => 'Verification request not found',
            'reason.required' => 'Please select at least one rejection reason',
            'reason.array' => 'Reasons must be a list',
            'reason.min' => 'Please select at least one rejection reason',
            'reason.*.enum' => 'One of the selected reasons is invalid',
            'description.max' => 'Description may not be greater than 1000 characters',
        ];
    }
}
