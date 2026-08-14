<?php

namespace App\Http\Requests;

use App\Enums\CertificateRejectionReason;
use App\Enums\RejectionReason;
use App\Services\CertificateService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RejectCertificateRequest extends FormRequest
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
            'rejection_reason'=>['required',Rule::enum(CertificateRejectionReason::class)]
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Rejection reason is required',
            'rejection_reason.enum' => 'The selected rejection reason is invalid',
        ];
    }
}
