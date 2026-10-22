<?php

namespace App\Http\Requests;

use App\Enums\Priority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTestCaseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'preconditions' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'steps' => ['sometimes', 'required', 'string', 'max:5000'],
            'expected_result' => ['sometimes', 'required', 'string', 'max:5000'],
            'priority' => ['sometimes', Rule::enum(Priority::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
