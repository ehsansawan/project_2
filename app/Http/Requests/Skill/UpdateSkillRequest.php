<?php

namespace App\Http\Requests\Skill;

use App\Enums\SkillType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateSkillRequest extends FormRequest
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
            'name'=>'string',
            'type'=>[new Enum(SkillType::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Skill name must be a string',
            'type.enum' => 'The selected skill type is invalid',
        ];
    }
}
