<?php

namespace App\Http\Requests;

use App\Enums\Priority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTestCaseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'preconditions' => ['nullable', 'string', 'max:5000'],
            'steps' => ['required', 'string', 'max:5000'],
            'expected_result' => ['required', 'string', 'max:5000'],
            'priority' => ['sometimes', Rule::enum(Priority::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
