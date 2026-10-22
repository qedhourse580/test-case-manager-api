<?php

namespace App\Http\Requests;

use App\Enums\ResultStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordTestResultRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ResultStatus::class)],
            // 失敗・ブロックのときは、原因を残すためにメモを必須にする
            'note' => [
                Rule::requiredIf(fn () => in_array($this->input('status'), [
                    ResultStatus::Failed->value,
                    ResultStatus::Blocked->value,
                ], true)),
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'ステータスが failed または blocked の場合は、note（理由）を入力してください。',
        ];
    }
}
