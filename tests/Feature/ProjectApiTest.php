<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function プロジェクトを作成できる(): void
    {
        $response = $this->postJson('/api/projects', [
            'name' => 'サンプルプロジェクト',
            'description' => '説明文',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'サンプルプロジェクト');
        $this->assertDatabaseHas('projects', ['name' => 'サンプルプロジェクト']);
    }

    #[Test]
    public function 名前が未入力の場合は422を返す(): void
    {
        $this->postJson('/api/projects', ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    #[Test]
    public function 名前は100文字まで登録でき101文字は登録できない(): void
    {
        $this->postJson('/api/projects', ['name' => str_repeat('あ', 100)])->assertCreated();
        $this->postJson('/api/projects', ['name' => str_repeat('あ', 101)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    #[Test]
    public function 一覧に件数の集計が含まれる(): void
    {
        $project = Project::factory()->hasTestCases(3)->create();

        $this->getJson('/api/projects')
            ->assertOk()
            ->assertJsonPath('data.0.id', $project->id)
            ->assertJsonPath('data.0.test_cases_count', 3);
    }

    #[Test]
    public function プロジェクトを更新できる(): void
    {
        $project = Project::factory()->create();

        $this->patchJson("/api/projects/{$project->id}", ['name' => '新しい名前'])
            ->assertOk()
            ->assertJsonPath('data.name', '新しい名前');
    }

    #[Test]
    public function プロジェクトを削除すると配下のテストケースも削除される(): void
    {
        $project = Project::factory()->hasTestCases(2)->create();

        $this->deleteJson("/api/projects/{$project->id}")->assertNoContent();

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseCount('test_cases', 0);
    }

    #[Test]
    public function 存在しないプロジェクトは404を返す(): void
    {
        $this->getJson('/api/projects/9999')->assertNotFound();
    }
}
