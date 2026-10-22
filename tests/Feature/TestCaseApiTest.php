<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\TestCase as TestCaseModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestCaseApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function テストケースを作成でき優先度の初期値はmediumになる(): void
    {
        $project = Project::factory()->create();

        $this->postJson("/api/projects/{$project->id}/test-cases", [
            'title' => 'ログインできる',
            'steps' => '1. ログイン画面を開く',
            'expected_result' => 'トップ画面に遷移する',
        ])
            ->assertCreated()
            ->assertJsonPath('data.priority', 'medium')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.project_id', $project->id);
    }

    #[Test]
    public function 必須項目が欠けている場合は422を返す(): void
    {
        $project = Project::factory()->create();

        $this->postJson("/api/projects/{$project->id}/test-cases", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'steps', 'expected_result']);
    }

    #[Test]
    public function 定義されていない優先度は422を返す(): void
    {
        $project = Project::factory()->create();

        $this->postJson("/api/projects/{$project->id}/test-cases", [
            'title' => 'テスト',
            'steps' => '手順',
            'expected_result' => '結果',
            'priority' => 'urgent',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('priority');
    }

    #[Test]
    public function 一覧は自分のプロジェクトのテストケースだけを返す(): void
    {
        $project = Project::factory()->create();
        TestCaseModel::factory()->count(2)->for($project)->create();
        TestCaseModel::factory()->create(); // 別プロジェクト

        $this->getJson("/api/projects/{$project->id}/test-cases")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function 優先度と有効フラグで絞り込める(): void
    {
        $project = Project::factory()->create();
        TestCaseModel::factory()->for($project)->create(['priority' => 'high']);
        TestCaseModel::factory()->for($project)->create(['priority' => 'low']);
        TestCaseModel::factory()->for($project)->inactive()->create(['priority' => 'high']);

        $this->getJson("/api/projects/{$project->id}/test-cases?priority=high&is_active=1")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.priority', 'high')
            ->assertJsonPath('data.0.is_active', true);
    }

    #[Test]
    public function テストケースを更新できる(): void
    {
        $testCase = TestCaseModel::factory()->create();

        $this->patchJson("/api/test-cases/{$testCase->id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }

    #[Test]
    public function テストケースを削除できる(): void
    {
        $testCase = TestCaseModel::factory()->create();

        $this->deleteJson("/api/test-cases/{$testCase->id}")->assertNoContent();
        $this->assertDatabaseMissing('test_cases', ['id' => $testCase->id]);
    }
}
