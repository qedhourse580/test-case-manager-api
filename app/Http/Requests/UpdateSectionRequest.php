<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 名前と並び順のみ変更できる（親の付け替えはできない）
 */
class UpdateSectionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
