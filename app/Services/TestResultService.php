<?php

namespace App\Services;

use App\Enums\ResultStatus;
use App\Models\TestResult;
use Illuminate\Support\Facades\DB;

class TestResultService
{
    /**
     * テスト結果を記録する
     *
     * test_results には最新の結果を保持し、記録のたびに test_result_histories へ
     * 1行追加する（NG → 修正 → 再テストで OK、という経緯を残すため）
     */
    public function record(TestResult $result, ResultStatus $status, ?string $note): TestResult
    {
        return DB::transaction(function () use ($result, $status, $note) {
            // 未実施に戻した場合は実施日時もクリアする
            $executedAt = $status->isExecuted() ? now() : null;

            $result->update([
                'status' => $status,
                'note' => $note,
                'executed_at' => $executedAt,
            ]);

            $result->histories()->create([
                'status' => $status,
                'note' => $note,
                'executed_at' => $executedAt,
            ]);

            return $result;
        });
    }
}
