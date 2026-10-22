<?php

namespace App\Http\Requests;

use App\Enums\DefectStatus;
use App\Models\Defect;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ChangeDefectStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(DefectStatus::class)],
        ];
    }

    /**
     * 許可されていない状態遷移（例：closed → open）を弾く
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('status')) {
                    return;
                }

                /** @var Defect $defect */
                $defect = $this->route('defect');
                $next = DefectStatus::from($this->input('status'));

                if (! $defect->status->canTransitionTo($next)) {
                    $validator->errors()->add(
                        'status',
                        "ステータスを {$defect->status->value} から {$next->value} に変更することはできません。",
                    );
                }
            },
        ];
    }
}
