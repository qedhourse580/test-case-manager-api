<?php

namespace App\Http\Requests;

use App\Enums\SectionLevel;
use App\Models\Project;
use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSectionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', 'exists:sections,id'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * 親セクションが同じプロジェクトのものか、階層が4段を超えないかを確認する
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $parentId = $this->input('parent_id');
                if ($parentId === null || $validator->errors()->has('parent_id')) {
                    return;
                }

                /** @var Project $project */
                $project = $this->route('project');
                $parent = Section::find($parentId);

                if ($parent->project_id !== $project->id) {
                    $validator->errors()->add('parent_id', '別のプロジェクトのセクションは親にできません。');

                    return;
                }

                if ($parent->depth >= SectionLevel::MAX_DEPTH) {
                    $validator->errors()->add('parent_id', '階層は「シート → 大項目 → 中項目 → 小項目」の4段までです。');
                }
            },
        ];
    }
}
