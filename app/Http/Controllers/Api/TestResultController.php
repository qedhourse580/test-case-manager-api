<?php

namespace App\Http\Controllers\Api;

use App\Enums\ResultStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordTestResultRequest;
use App\Http\Resources\TestResultResource;
use App\Models\TestResult;

class TestResultController extends Controller
{
    /**
     * テスト結果を記録する
     */
    public function update(RecordTestResultRequest $request, TestResult $testResult): TestResultResource
    {
        $status = ResultStatus::from($request->validated('status'));

        $testResult->update([
            'status' => $status,
            'note' => $request->validated('note'),
            // 未実施に戻した場合は実施日時もクリアする
            'executed_at' => $status->isExecuted() ? now() : null,
        ]);

        return TestResultResource::make($testResult->load('testCase'));
    }
}
