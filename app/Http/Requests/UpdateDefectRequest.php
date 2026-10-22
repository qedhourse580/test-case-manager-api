<?php

namespace App\Http\Requests;

use App\Enums\Severity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDefectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'steps_to_reproduce' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'severity' => ['sometimes', Rule::enum(Severity::class)],
        ];
    }
}
