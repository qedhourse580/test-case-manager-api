<?php

namespace App\Http\Requests;

use App\Enums\Priority;
use App\Http\Requests\Concerns\ValidatesSectionBelongsToProject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTestCaseRequest extends FormRequest
{
    use ValidatesSectionBelongsToProject;

    public function rules(): array
    {
        return [
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'title' => ['required', 'string', 'max:200'],
            'preconditions' => ['nullable', 'string', 'max:5000'],
            'steps' => ['required', 'string', 'max:5000'],
            'expected_result' => ['required', 'string', 'max:5000'],
            'priority' => ['sometimes', Rule::enum(Priority::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function projectId(): int
    {
        return $this->route('project')->id;
    }
}
