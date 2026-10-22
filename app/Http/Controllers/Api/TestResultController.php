<?php

namespace App\Http\Controllers\Api;

use App\Enums\ResultStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordTestResultRequest;
use App\Http\Resources\TestResultResource;
use App\Models\TestResult;
use App\Services\TestResultService;

class TestResultController extends Controller
{
    public function __construct(private readonly TestResultService $service) {}

    /**
     * テスト結果を記録する（履歴にも1行追加される）
     */
    public function update(RecordTestResultRequest $request, TestResult $testResult): TestResultResource
    {
        $this->service->record(
            $testResult,
            ResultStatus::from($request->validated('status')),
            $request->validated('note'),
        );

        return TestResultResource::make($testResult->load('testCase')->loadCount('histories'));
    }

    /**
     * 記録の履歴（古い順）
     */
    public function history(TestResult $testResult): TestResultResource
    {
        return TestResultResource::make(
            $testResult->load(['testCase', 'histories'])->loadCount('histories'),
        );
    }
}
