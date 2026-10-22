<?php

namespace App\Services;

use App\Enums\ResultStatus;
use App\Models\Project;
use App\Models\TestRun;
use Illuminate\Support\Facades\DB;

class TestRunService
{
    /**
     * テスト実行を作成し、有効なテストケースごとに「未実施」の結果を用意する
     *
     * @param  array{name: string, environment?: string|null}  $attributes
     */
    public function create(Project $project, array $attributes): TestRun
    {
        return DB::transaction(function () use ($project, $attributes) {
            /** @var TestRun $run */
            $run = $project->testRuns()->create($attributes);

            $now = now();
            $rows = $project->testCases()
                ->active()
                ->orderBy('id')
                ->pluck('id')
                ->map(fn (int $caseId) => [
                    'test_run_id' => $run->id,
                    'test_case_id' => $caseId,
                    'status' => ResultStatus::Untested->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();

            if ($rows !== []) {
                $run->results()->insert($rows);
            }

            return $run;
        });
    }

    /**
     * テスト実行の集計（進捗率・合格率）を返す
     *
     * - progress_rate：全件のうち、実施済み（untested 以外）の割合
     * - pass_rate：合否を判定した件数（passed + failed + blocked）のうち、passed の割合。
     *   skipped は合否の分母に含めない
     *
     * @return array<string, mixed>
     */
    public function summarize(TestRun $run): array
    {
        $counts = $run->results()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $byStatus = [];
        foreach (ResultStatus::cases() as $status) {
            $byStatus[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        $total = array_sum($byStatus);
        $executed = $total - $byStatus[ResultStatus::Untested->value];
        $judged = $byStatus[ResultStatus::Passed->value]
            + $byStatus[ResultStatus::Failed->value]
            + $byStatus[ResultStatus::Blocked->value];

        return [
            'test_run_id' => $run->id,
            'total' => $total,
            'executed' => $executed,
            'by_status' => $byStatus,
            'progress_rate' => $total > 0 ? round($executed / $total * 100, 1) : 0.0,
            'pass_rate' => $judged > 0 ? round($byStatus[ResultStatus::Passed->value] / $judged * 100, 1) : 0.0,
        ];
    }
}
