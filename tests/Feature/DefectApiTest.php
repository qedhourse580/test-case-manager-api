<?php

namespace Tests\Feature;

use App\Enums\DefectStatus;
use App\Models\Defect;
use App\Models\Project;
use App\Models\TestResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DefectApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 不具合を作成すると初期ステータスはopenになる(): void
    {
        $project = Project::factory()->create();

        $this->postJson("/api/projects/{$project->id}/defects", [
            'title' => 'ログインボタンが反応しない',
            'severity' => 'major',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.allowed_next_statuses', ['in_progress', 'rejected']);
    }

    #[Test]
    public function 同じプロジェクトのテスト結果に紐づけられる(): void
    {
        $result = TestResult::factory()->create();
        $projectId = $result->testRun->project_id;

        $this->postJson("/api/projects/{$projectId}/defects", [
            'title' => '不具合',
            'severity' => 'minor',
            'test_result_id' => $result->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.test_result_id', $result->id);
    }

    #[Test]
    public function 別プロジェクトのテスト結果には紐づけられない(): void
    {
        $result = TestResult::factory()->create();
        $otherProject = Project::factory()->create();

        $this->postJson("/api/projects/{$otherProject->id}/defects", [
            'title' => '不具合',
            'severity' => 'minor',
            'test_result_id' => $result->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('test_result_id');
    }

    #[Test]
    public function ステータスとseverityで絞り込める(): void
    {
        $project = Project::factory()->create();
        Defect::factory()->for($project)->create(['severity' => 'critical']);
        Defect::factory()->for($project)->status(DefectStatus::Closed)->create(['severity' => 'critical']);
        Defect::factory()->for($project)->create(['severity' => 'minor']);

        $this->getJson("/api/projects/{$project->id}/defects?status=open&severity=critical")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function 許可された遷移ならステータスを変更できる(): void
    {
        $defect = Defect::factory()->create();

        $this->patchJson("/api/defects/{$defect->id}/status", ['status' => 'in_progress'])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        $this->patchJson("/api/defects/{$defect->id}/status", ['status' => 'resolved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'resolved');
    }

    #[Test]
    public function 許可されていない遷移は422を返しステータスは変わらない(): void
    {
        $defect = Defect::factory()->status(DefectStatus::Closed)->create();

        $this->patchJson("/api/defects/{$defect->id}/status", ['status' => 'open'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertSame(DefectStatus::Closed, $defect->refresh()->status);
    }

    #[Test]
    public function 通常の更新ではステータスを変更できない(): void
    {
        $defect = Defect::factory()->create();

        $this->patchJson("/api/defects/{$defect->id}", ['title' => '変更後', 'status' => 'closed'])
            ->assertOk()
            ->assertJsonPath('data.title', '変更後')
            ->assertJsonPath('data.status', 'open');
    }

    #[Test]
    public function ステータス別と重要度別に集計できる(): void
    {
        $project = Project::factory()->create();
        Defect::factory()->for($project)->create(['severity' => 'critical']);
        Defect::factory()->for($project)->status(DefectStatus::InProgress)->create(['severity' => 'major']);
        Defect::factory()->for($project)->status(DefectStatus::Closed)->create(['severity' => 'major']);
        Defect::factory()->create(); // 別プロジェクト

        $this->getJson("/api/projects/{$project->id}/defects/summary")
            ->assertOk()
            ->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.unresolved', 2)
            ->assertJsonPath('data.by_status.closed', 1)
            ->assertJsonPath('data.by_severity.major', 2)
            ->assertJsonPath('data.by_severity.trivial', 0);
    }
}
