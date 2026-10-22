<?php

namespace App\Http\Requests\Concerns;

use App\Models\Section;
use Illuminate\Validation\Validator;

/**
 * テストケースに指定した section_id が、同じプロジェクトのものかを確認する
 */
trait ValidatesSectionBelongsToProject
{
    abstract protected function projectId(): int;

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $sectionId = $this->input('section_id');
                if ($sectionId === null || $validator->errors()->has('section_id')) {
                    return;
                }

                if (Section::find($sectionId)->project_id !== $this->projectId()) {
                    $validator->errors()->add('section_id', '別のプロジェクトのセクションには登録できません。');
                }
            },
        ];
    }
}
