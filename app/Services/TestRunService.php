<?php

namespace App\Services;

use App\Enums\ResultStatus;
use App\Models\Project;
use App\Models\Section;
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
     * テスト実行の集計（進捗率・合格率・再テスト）を返す
     *
     * - progress_rate：全件のうち、実施済み（untested 以外）の割合
     * - pass_rate：合否を判定した件数（passed + failed + blocked）のうち、passed の割合。
     *   skipped は合否の分母に含めない
     * - retested_passed：一度 failed になり、再テストで passed になった件数
     *
     * @return array<string, mixed>
     */
    public function summarize(TestRun $run): array
    {
        $counts = $run->results()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $retestedPassed = $run->results()
            ->where('status', ResultStatus::Passed->value)
            ->whereHas('histories', fn ($q) => $q->where('status', ResultStatus::Failed->value))
            ->count();

        return [
            'test_run_id' => $run->id,
            ...$this->buildCounts($counts),
            'retested_passed' => $retestedPassed,
        ];
    }

    /**
     * シート・大項目・中項目・小項目ごとの集計をツリー形式で返す
     *
     * 各セクションの件数には、配下（下位セクション）の件数も含める。
     * セクションに属していないテストケースは「未分類」としてまとめる
     *
     * @return array<string, mixed>
     */
    public function summarizeBySection(TestRun $run): array
    {
        // セクションID → ステータス → 件数（そのセクションに直接属する分）
        $direct = [];
        $rows = $run->results()
            ->join('test_cases', 'test_cases.id', '=', 'test_results.test_case_id')
            ->selectRaw('test_cases.section_id as section_id, test_results.status as status, COUNT(*) as aggregate')
            ->groupBy('test_cases.section_id', 'test_results.status')
            ->toBase() // 集計行なので、モデルのキャスト（Enum化）をかけずに受け取る
            ->get();
        foreach ($rows as $row) {
            $key = $row->section_id ?? 'unassigned';
            $direct[$key][$row->status] = (int) $row->aggregate;
        }

        $sections = Section::where('project_id', $run->project_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'parent_id', 'name', 'depth'])
            ->groupBy(fn (Section $s) => $s->parent_id ?? 0);

        $tree = $this->buildSectionTree($sections, 0, $direct)['nodes'];

        if (isset($direct['unassigned'])) {
            $tree[] = [
                'id' => null,
                'name' => '未分類',
                'level' => null,
                ...$this->buildCounts($direct['unassigned']),
                'children' => [],
            ];
        }

        return [
            'test_run_id' => $run->id,
            'sections' => $tree,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, Section>>  $sectionsByParent
     * @param  array<int|string, array<string, int>>  $direct
     * @return array{nodes: list<array<string, mixed>>, counts: array<string, int>}
     */
    private function buildSectionTree($sectionsByParent, int $parentId, array $direct): array
    {
        $nodes = [];
        $total = [];

        foreach ($sectionsByParent->get($parentId, collect()) as $section) {
            $child = $this->buildSectionTree($sectionsByParent, $section->id, $direct);

            // 自分に直接属する件数 + 配下の件数
            $counts = $direct[$section->id] ?? [];
            foreach ($child['counts'] as $status => $n) {
                $counts[$status] = ($counts[$status] ?? 0) + $n;
            }

            $nodes[] = [
                'id' => $section->id,
                'name' => $section->name,
                'level' => $section->level()->label(),
                ...$this->buildCounts($counts),
                'children' => $child['nodes'],
            ];

            foreach ($counts as $status => $n) {
                $total[$status] = ($total[$status] ?? 0) + $n;
            }
        }

        return ['nodes' => $nodes, 'counts' => $total];
    }

    /**
     * ステータス別の件数から、合計・実施数・進捗率・合格率を計算する
     *
     * @param  array<string, int|string>  $counts
     * @return array<string, mixed>
     */
    private function buildCounts(array $counts): array
    {
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
            'total' => $total,
            'executed' => $executed,
            'by_status' => $byStatus,
            'progress_rate' => $total > 0 ? round($executed / $total * 100, 1) : 0.0,
            'pass_rate' => $judged > 0 ? round($byStatus[ResultStatus::Passed->value] / $judged * 100, 1) : 0.0,
        ];
    }
}
