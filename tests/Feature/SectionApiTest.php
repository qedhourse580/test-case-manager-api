<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Section;
use App\Models\TestCase as TestCaseModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SectionApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function シートから小項目まで4階層を作成できる(): void
    {
        $project = Project::factory()->create();
        $parentId = null;
        $expected = ['シート', '大項目', '中項目', '小項目'];

        foreach ($expected as $i => $level) {
            $response = $this->postJson("/api/projects/{$project->id}/sections", [
                'name' => "{$level}A",
                'parent_id' => $parentId,
            ]);

            $response->assertCreated()
                ->assertJsonPath('data.depth', $i + 1)
                ->assertJsonPath('data.level', $level);
            $parentId = $response->json('data.id');
        }
    }

    #[Test]
    public function 小項目の下には作成できない(): void
    {
        $sheet = Section::factory()->create();
        $large = Section::factory()->childOf($sheet)->create();
        $medium = Section::factory()->childOf($large)->create();
        $small = Section::factory()->childOf($medium)->create();

        $this->postJson("/api/projects/{$sheet->project_id}/sections", [
            'name' => '5階層目',
            'parent_id' => $small->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');
    }

    #[Test]
    public function 別プロジェクトのセクションを親にはできない(): void
    {
        $otherSheet = Section::factory()->create();
        $project = Project::factory()->create();

        $this->postJson("/api/projects/{$project->id}/sections", [
            'name' => '大項目',
            'parent_id' => $otherSheet->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');
    }

    #[Test]
    public function 一覧はツリー形式で並び順どおりに返す(): void
    {
        $project = Project::factory()->create();
        $sheet2 = Section::factory()->for($project)->create(['name' => 'シート2', 'sort_order' => 2]);
        $sheet1 = Section::factory()->for($project)->create(['name' => 'シート1', 'sort_order' => 1]);
        $large = Section::factory()->childOf($sheet1)->create(['name' => 'ログイン']);
        TestCaseModel::factory()->for($project)->create(['section_id' => $large->id]);

        $this->getJson("/api/projects/{$project->id}/sections")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'シート1')
            ->assertJsonPath('data.0.children.0.name', 'ログイン')
            ->assertJsonPath('data.0.children.0.test_cases_count', 1)
            ->assertJsonPath('data.1.name', 'シート2');
    }

    #[Test]
    public function 名前と並び順を変更できる(): void
    {
        $section = Section::factory()->create();

        $this->patchJson("/api/sections/{$section->id}", ['name' => '画面A', 'sort_order' => 5])
            ->assertOk()
            ->assertJsonPath('data.name', '画面A')
            ->assertJsonPath('data.sort_order', 5);
    }

    #[Test]
    public function 削除すると下位セクションも削除されテストケースは未分類になる(): void
    {
        $sheet = Section::factory()->create();
        $large = Section::factory()->childOf($sheet)->create();
        $testCase = TestCaseModel::factory()->create([
            'project_id' => $sheet->project_id,
            'section_id' => $large->id,
        ]);

        $this->deleteJson("/api/sections/{$sheet->id}")->assertNoContent();

        $this->assertDatabaseCount('sections', 0);
        $this->assertNull($testCase->refresh()->section_id);
    }

    #[Test]
    public function テストケースをセクションに登録できる(): void
    {
        $section = Section::factory()->create();

        $this->postJson("/api/projects/{$section->project_id}/test-cases", [
            'section_id' => $section->id,
            'title' => 'IDに日本語を入力するとエラーになる',
            'steps' => '1. ID欄に「やまだ」と入力する',
            'expected_result' => 'エラーメッセージが表示される',
        ])
            ->assertCreated()
            ->assertJsonPath('data.section_id', $section->id);
    }

    #[Test]
    public function 別プロジェクトのセクションにはテストケースを登録できない(): void
    {
        $otherSection = Section::factory()->create();
        $project = Project::factory()->create();

        $this->postJson("/api/projects/{$project->id}/test-cases", [
            'section_id' => $otherSection->id,
            'title' => 'テスト',
            'steps' => '手順',
            'expected_result' => '結果',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('section_id');

        $testCase = TestCaseModel::factory()->for($project)->create();
        $this->patchJson("/api/test-cases/{$testCase->id}", ['section_id' => $otherSection->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('section_id');
    }

    #[Test]
    public function セクションでテストケースを絞り込める(): void
    {
        $section = Section::factory()->create();
        TestCaseModel::factory()->count(2)->create(['project_id' => $section->project_id, 'section_id' => $section->id]);
        TestCaseModel::factory()->create(['project_id' => $section->project_id]);

        $this->getJson("/api/projects/{$section->project_id}/test-cases?section_id={$section->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
