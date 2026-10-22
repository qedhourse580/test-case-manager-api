<?php

namespace App\Http\Requests;

use App\Enums\Priority;
use App\Http\Requests\Concerns\ValidatesSectionBelongsToProject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTestCaseRequest extends FormRequest
{
    use ValidatesSectionBelongsToProject;

    public function rules(): array
    {
        return [
            'section_id' => ['sometimes', 'nullable', 'integer', 'exists:sections,id'],
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'preconditions' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'steps' => ['sometimes', 'required', 'string', 'max:5000'],
            'expected_result' => ['sometimes', 'required', 'string', 'max:5000'],
            'priority' => ['sometimes', Rule::enum(Priority::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function projectId(): int
    {
        return $this->route('test_case')->project_id;
    }
}
