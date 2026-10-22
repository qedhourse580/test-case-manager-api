<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Section;
use App\Models\TestCase as TestCaseModel;
use App\Models\TestRun;
use App\Services\TestRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SectionSummaryApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function セクションごとの集計が配下を含めてツリー形式で返る(): void
    {
        $project = Project::factory()->create();
        $sheet = Section::factory()->for($project)->create(['name' => 'ログイン画面']);
        $idItem = Section::factory()->childOf($sheet)->create(['name' => 'ID入力欄']);
        $pwItem = Section::factory()->childOf($sheet)->create(['name' => 'パスワード入力欄']);

        $idCases = TestCaseModel::factory()->count(2)->for($project)->create(['section_id' => $idItem->id]);
        $pwCase = TestCaseModel::factory()->for($project)->create(['section_id' => $pwItem->id]);
        $unassigned = TestCaseModel::factory()->for($project)->create();

        $run = app(TestRunService::class)->create($project, ['name' => 'run']);
        $resultOf = fn (TestCaseModel $c) => $run->results()->where('test_case_id', $c->id)->first();

        $this->patchJson("/api/test-results/{$resultOf($idCases[0])->id}", ['status' => 'passed']);
        $this->patchJson("/api/test-results/{$resultOf($idCases[1])->id}", ['status' => 'failed', 'note' => 'NG']);
        $this->patchJson("/api/test-results/{$resultOf($pwCase)->id}", ['status' => 'passed']);

        $response = $this->getJson("/api/test-runs/{$run->id}/sections-summary")->assertOk();

        $response
            // シート：配下の3件を合算（passed 2 / failed 1）
            ->assertJsonPath('data.sections.0.name', 'ログイン画面')
            ->assertJsonPath('data.sections.0.level', 'シート')
            ->assertJsonPath('data.sections.0.total', 3)
            ->assertJsonPath('data.sections.0.progress_rate', 100)
            ->assertJsonPath('data.sections.0.pass_rate', 66.7)
            // 大項目：ID入力欄（passed 1 / failed 1）
            ->assertJsonPath('data.sections.0.children.0.name', 'ID入力欄')
            ->assertJsonPath('data.sections.0.children.0.level', '大項目')
            ->assertJsonPath('data.sections.0.children.0.total', 2)
            ->assertJsonPath('data.sections.0.children.0.pass_rate', 50)
            // 大項目：パスワード入力欄（passed 1）
            ->assertJsonPath('data.sections.0.children.1.total', 1)
            ->assertJsonPath('data.sections.0.children.1.pass_rate', 100)
            // セクションに属さないテストケースは「未分類」
            ->assertJsonPath('data.sections.1.name', '未分類')
            ->assertJsonPath('data.sections.1.total', 1)
            ->assertJsonPath('data.sections.1.executed', 0);

        $this->assertNotNull($unassigned);
    }

    #[Test]
    public function テストケースのないセクションは0件として返る(): void
    {
        $project = Project::factory()->create();
        Section::factory()->for($project)->create(['name' => '空のシート']);
        $run = TestRun::factory()->for($project)->create();

        $this->getJson("/api/test-runs/{$run->id}/sections-summary")
            ->assertOk()
            ->assertJsonCount(1, 'data.sections')
            ->assertJsonPath('data.sections.0.total', 0)
            ->assertJsonPath('data.sections.0.progress_rate', 0);
    }
}
