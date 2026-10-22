<?php

namespace Database\Seeders;

use App\Enums\DefectStatus;
use App\Enums\ResultStatus;
use App\Enums\Severity;
use App\Models\Project;
use App\Models\Section;
use App\Services\TestResultService;
use App\Services\TestRunService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * 動作確認用のサンプルデータ（ECサイトのログイン画面・カート画面のテスト）
     */
    public function run(TestRunService $runs, TestResultService $results): void
    {
        $project = Project::create([
            'name' => 'サンプルECサイト',
            'description' => 'ログイン画面・カート画面の回帰テスト',
        ]);

        // シート → 大項目 → 中項目
        $tree = [
            'ログイン画面' => [
                'ID入力欄' => [
                    '入力制限' => [
                        ['半角英数字のIDを入力できる', 'high'],
                        ['IDに日本語を入力するとエラーになる', 'high'],
                    ],
                ],
                'パスワード入力欄' => [
                    '表示' => [
                        ['パスワードが伏せ字（●）で表示される', 'high'],
                    ],
                ],
            ],
            'カート画面' => [
                '数量変更' => [
                    '金額計算' => [
                        ['数量を変更すると合計金額が再計算される', 'medium'],
                        ['在庫数を超える数量は指定できない', 'low'],
                    ],
                ],
            ],
        ];

        $sheetOrder = 0;
        foreach ($tree as $sheetName => $larges) {
            $sheet = $this->section($project, null, $sheetName, ++$sheetOrder);
            foreach ($larges as $largeName => $mediums) {
                $large = $this->section($project, $sheet, $largeName);
                foreach ($mediums as $mediumName => $cases) {
                    $medium = $this->section($project, $large, $mediumName);
                    foreach ($cases as [$title, $priority]) {
                        $project->testCases()->create([
                            'section_id' => $medium->id,
                            'title' => $title,
                            'preconditions' => "{$sheetName}を開いている",
                            'steps' => "{$largeName}を操作する",
                            'expected_result' => $title,
                            'priority' => $priority,
                        ]);
                    }
                }
            }
        }

        $run = $runs->create($project, ['name' => 'v1.2.0 リリース前回帰テスト', 'environment' => 'staging']);
        $list = $run->results()->orderBy('id')->get();

        // 1件目：OK
        $results->record($list[0], ResultStatus::Passed, null);
        // 2件目：NG → 修正 → 再テストで OK
        $results->record($list[1], ResultStatus::Failed, 'エラーメッセージが表示されない');
        $defect = $project->defects()->create([
            'test_result_id' => $list[1]->id,
            'title' => 'IDに日本語を入力してもエラーメッセージが表示されない',
            'steps_to_reproduce' => "1. ログイン画面を開く\n2. ID欄に「やまだ」と入力してログインする",
            'severity' => Severity::Major,
        ]);
        foreach ([DefectStatus::InProgress, DefectStatus::Resolved, DefectStatus::Closed] as $status) {
            $defect->status = $status;
            $defect->save();
        }
        $results->record($list[1], ResultStatus::Passed, '修正後に再テストしてOK');
        // 3件目：OK
        $results->record($list[2], ResultStatus::Passed, null);
        // 4件目：ブロック（4件目・5件目のカート画面は未完了）
        $results->record($list[3], ResultStatus::Blocked, 'テスト環境の在庫データが未準備');
    }

    private function section(Project $project, ?Section $parent, string $name, int $sortOrder = 0): Section
    {
        $section = new Section(['name' => $name, 'sort_order' => $sortOrder]);
        $section->project()->associate($project);
        $section->parent_id = $parent?->id;
        $section->depth = $parent ? $parent->depth + 1 : 1;
        $section->save();

        return $section;
    }
}
