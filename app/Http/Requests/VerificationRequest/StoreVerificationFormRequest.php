<?php

namespace App\Http\Requests\VerificationRequest;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVerificationFormRequest extends FormRequest
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
            'national_id' => ['required',
                'string',
                'regex:/^[0-9]{11}$/',
                'unique:users,national_id'],
            'images' => 'required|array|size:2',
            'images.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:4096',

        ];
    }

}
