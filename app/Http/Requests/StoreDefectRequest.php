<?php

namespace App\Http\Requests;

use App\Enums\Severity;
use App\Models\Project;
use App\Models\TestResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDefectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'steps_to_reproduce' => ['nullable', 'string', 'max:5000'],
            'severity' => ['required', Rule::enum(Severity::class)],
            'test_result_id' => ['nullable', 'integer', 'exists:test_results,id'],
        ];
    }

    /**
     * 紐づけるテスト結果が、同じプロジェクトのものかを確認する
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $resultId = $this->input('test_result_id');
                if ($resultId === null || $validator->errors()->has('test_result_id')) {
                    return;
                }

                /** @var Project $project */
                $project = $this->route('project');
                $result = TestResult::with('testRun')->find($resultId);

                if ($result->testRun->project_id !== $project->id) {
                    $validator->errors()->add('test_result_id', '別のプロジェクトのテスト結果は紐づけられません。');
                }
            },
        ];
    }
}
