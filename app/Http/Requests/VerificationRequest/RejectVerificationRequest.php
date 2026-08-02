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

            'reason' => [
                'required',
                new Enum(RejectionReason::class),
            ],

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
}
