<?php

namespace Database\Seeders;

use App\Enums\DefectStatus;
use App\Enums\ResultStatus;
use App\Enums\Severity;
use App\Models\Project;
use App\Models\TestCase;
use App\Services\TestRunService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * 動作確認用のサンプルデータ（ECサイトのログイン・購入機能のテスト）
     */
    public function run(TestRunService $service): void
    {
        $project = Project::create([
            'name' => 'サンプルECサイト',
            'description' => 'ログイン・カート・購入機能の回帰テスト',
        ]);

        $cases = [
            ['正しいメールアドレスとパスワードでログインできる', 'high'],
            ['誤ったパスワードではログインできず、エラーメッセージが表示される', 'high'],
            ['商品をカートに追加できる', 'medium'],
            ['カート内の数量を変更すると合計金額が再計算される', 'medium'],
            ['在庫切れの商品は購入ボタンが押せない', 'low'],
        ];

        foreach ($cases as [$title, $priority]) {
            $project->testCases()->create([
                'title' => $title,
                'steps' => '画面仕様書の手順に従って操作する',
                'expected_result' => $title,
                'priority' => $priority,
            ]);
        }

        $run = $service->create($project, ['name' => 'v1.2.0 リリース前回帰テスト', 'environment' => 'staging']);

        $statuses = [ResultStatus::Passed, ResultStatus::Failed, ResultStatus::Passed, ResultStatus::Blocked, ResultStatus::Untested];
        foreach ($run->results()->orderBy('id')->get() as $i => $result) {
            $result->update([
                'status' => $statuses[$i],
                'note' => in_array($statuses[$i], [ResultStatus::Failed, ResultStatus::Blocked], true) ? '詳細は不具合チケットを参照' : null,
                'executed_at' => $statuses[$i]->isExecuted() ? now() : null,
            ]);
        }

        $failed = $run->results()->where('status', ResultStatus::Failed->value)->first();
        $defect = $project->defects()->create([
            'test_result_id' => $failed->id,
            'title' => '誤ったパスワードでもエラーメッセージが表示されない',
            'steps_to_reproduce' => "1. ログイン画面を開く\n2. 誤ったパスワードを入力してログインする",
            'severity' => Severity::Major,
        ]);
        $defect->status = DefectStatus::InProgress;
        $defect->save();
    }
}
