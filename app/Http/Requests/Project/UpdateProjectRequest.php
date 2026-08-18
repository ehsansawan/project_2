<?php

namespace App\Http\Requests\Project;

use App\Enums\SkillType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class UpdateProjectRequest extends FormRequest
{
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
            'id' => ['required', 'integer', 'exists:projects,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'media' => ['nullable', 'array'],
            'media.*' => [
                'file',
                'mimes:jpg,jpeg,png,webp,mp4,mov',
                'max:20480',
            ],
            'requires_volunteers' => ['nullable', 'boolean'],
            'requires_donations' => ['nullable', 'boolean'],
            'is_votable' => ['nullable', 'boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'voting_ends_at' => ['nullable', 'date', 'after:now'],
            'requirements' => ['nullable', 'array'],
            'requirements.*.skill_name' => ['nullable', 'string', 'max:255'],
            'requirements.*.skill_type' => ['nullable', new Enum(SkillType::class)],
            'requirements.*.required_count' => ['required_with:requirements', 'integer', 'min:1'],
            'requirements.*.is_need_certificate' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $requirements = $this->input('requirements', []);

            if (!is_array($requirements)) {
                return;
            }

            $seen = [];
            foreach ($requirements as $requirement) {
                $skillType = $requirement['skill_type'] ?? 'general';
                if (in_array($skillType, $seen, true)) {
                    $validator->errors()->add('requirements', "Duplicate requirement for skill type '{$skillType}'. Each skill type (or the general slot) may only appear once.");
                }
                $seen[] = $skillType;
            }
        });
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
            'id.required' => 'Project ID is required',
            'id.exists' => 'Project not found',
            'name.max' => 'Project title may not be greater than 255 characters',
            'media.*.file' => 'Each media must be a valid uploaded file',
            'media.*.mimes' => 'Unsupported file format. Allowed: JPG, PNG, WEBP, MP4, MOV',
            'media.*.max' => 'File size must not exceed 20MB',
            'latitude.between' => 'Latitude must be between -90 and 90',
            'longitude.between' => 'Longitude must be between -180 and 180',
            'voting_ends_at.after' => 'Voting deadline must be in the future',
            'requirements.*.required_count.required_with' => 'Please specify how many volunteers are required for this requirement',
        ];
    }
}
