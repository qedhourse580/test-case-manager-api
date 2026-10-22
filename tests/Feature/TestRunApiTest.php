<?php

namespace Tests\Feature;

use App\Enums\ResultStatus;
use App\Models\Project;
use App\Models\TestCase as TestCaseModel;
use App\Models\TestRun;
use App\Services\TestRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestRunApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function テスト実行を作成すると有効なテストケース分の未実施結果が作られる(): void
    {
        $project = Project::factory()->create();
        TestCaseModel::factory()->count(3)->for($project)->create();
        TestCaseModel::factory()->for($project)->inactive()->create();

        $response = $this->postJson("/api/projects/{$project->id}/test-runs", [
            'name' => 'Sprint 1 回帰テスト',
            'environment' => 'staging',
        ]);

        $response->assertCreated()->assertJsonPath('data.results_count', 3);
        $runId = $response->json('data.id');
        $this->assertSame(3, TestRun::find($runId)->results()->where('status', 'untested')->count());
    }

    #[Test]
    public function テストケースが0件でもテスト実行を作成できる(): void
    {
        $project = Project::factory()->create();

        $this->postJson("/api/projects/{$project->id}/test-runs", ['name' => '空の実行'])
            ->assertCreated()
            ->assertJsonPath('data.results_count', 0);
    }

    #[Test]
    public function テスト実行の詳細に結果とテストケース名が含まれる(): void
    {
        $project = Project::factory()->create();
        TestCaseModel::factory()->for($project)->create(['title' => 'ログインできる']);
        $run = app(TestRunService::class)->create($project, ['name' => 'run']);

        $this->getJson("/api/test-runs/{$run->id}")
            ->assertOk()
            ->assertJsonPath('data.results.0.test_case_title', 'ログインできる')
            ->assertJsonPath('data.results.0.status', 'untested');
    }

    #[Test]
    public function 集計で進捗率と合格率を返す(): void
    {
        $project = Project::factory()->create();
        TestCaseModel::factory()->count(5)->for($project)->create();
        $run = app(TestRunService::class)->create($project, ['name' => 'run']);

        // passed 2 / failed 1 / skipped 1 / untested 1
        $statuses = [ResultStatus::Passed, ResultStatus::Passed, ResultStatus::Failed, ResultStatus::Skipped];
        foreach ($run->results()->orderBy('id')->take(4)->get() as $i => $result) {
            $result->update(['status' => $statuses[$i]]);
        }

        $this->getJson("/api/test-runs/{$run->id}/summary")
            ->assertOk()
            ->assertJsonPath('data.total', 5)
            ->assertJsonPath('data.executed', 4)
            ->assertJsonPath('data.by_status.passed', 2)
            ->assertJsonPath('data.by_status.untested', 1)
            ->assertJsonPath('data.progress_rate', 80)
            // 合格率の分母は passed + failed + blocked（skipped は含めない）= 3
            ->assertJsonPath('data.pass_rate', 66.7);
    }

    #[Test]
    public function 結果が0件の集計は0除算にならない(): void
    {
        $run = TestRun::factory()->create();

        $this->getJson("/api/test-runs/{$run->id}/summary")
            ->assertOk()
            ->assertJsonPath('data.total', 0)
            ->assertJsonPath('data.progress_rate', 0)
            ->assertJsonPath('data.pass_rate', 0);
    }
}
