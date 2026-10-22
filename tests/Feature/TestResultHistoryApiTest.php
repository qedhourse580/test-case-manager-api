<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\TestCase as TestCaseModel;
use App\Models\TestResult;
use App\Services\TestRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestResultHistoryApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function NGから再テストでOKになった経緯が履歴に残る(): void
    {
        $result = TestResult::factory()->create();

        $this->patchJson("/api/test-results/{$result->id}", ['status' => 'failed', 'note' => 'エラーが表示されない'])
            ->assertOk()
            ->assertJsonPath('data.attempts', 1);

        $this->patchJson("/api/test-results/{$result->id}", ['status' => 'passed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'passed')
            ->assertJsonPath('data.attempts', 2);

        $this->getJson("/api/test-results/{$result->id}/history")
            ->assertOk()
            ->assertJsonPath('data.status', 'passed')
            ->assertJsonCount(2, 'data.history')
            ->assertJsonPath('data.history.0.status', 'failed')
            ->assertJsonPath('data.history.0.note', 'エラーが表示されない')
            ->assertJsonPath('data.history.1.status', 'passed');
    }

    #[Test]
    public function 入力エラーのときは履歴を追加しない(): void
    {
        $result = TestResult::factory()->create();

        $this->patchJson("/api/test-results/{$result->id}", ['status' => 'failed'])->assertUnprocessable();

        $this->assertDatabaseCount('test_result_histories', 0);
    }

    #[Test]
    public function 一度も記録していない結果の履歴は空(): void
    {
        $result = TestResult::factory()->create();

        $this->getJson("/api/test-results/{$result->id}/history")
            ->assertOk()
            ->assertJsonPath('data.attempts', 0)
            ->assertJsonCount(0, 'data.history');
    }

    #[Test]
    public function 集計にNGから再テストでOKになった件数が含まれる(): void
    {
        $project = Project::factory()->create();
        TestCaseModel::factory()->count(3)->for($project)->create();
        $run = app(TestRunService::class)->create($project, ['name' => 'run']);
        [$retested, $passedFirst, $stillFailed] = $run->results()->orderBy('id')->get()->all();

        // NG → OK
        $this->patchJson("/api/test-results/{$retested->id}", ['status' => 'failed', 'note' => 'NG']);
        $this->patchJson("/api/test-results/{$retested->id}", ['status' => 'passed']);
        // 最初から OK
        $this->patchJson("/api/test-results/{$passedFirst->id}", ['status' => 'passed']);
        // NG のまま
        $this->patchJson("/api/test-results/{$stillFailed->id}", ['status' => 'failed', 'note' => 'NG']);

        $this->getJson("/api/test-runs/{$run->id}/summary")
            ->assertOk()
            ->assertJsonPath('data.by_status.passed', 2)
            ->assertJsonPath('data.by_status.failed', 1)
            ->assertJsonPath('data.retested_passed', 1);
    }
}
