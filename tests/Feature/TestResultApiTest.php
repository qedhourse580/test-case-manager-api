<?php

namespace Tests\Feature;

use App\Models\TestResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestResultApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 合格を記録すると実施日時が入る(): void
    {
        $result = TestResult::factory()->create();

        $this->patchJson("/api/test-results/{$result->id}", ['status' => 'passed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'passed');

        $this->assertNotNull($result->refresh()->executed_at);
    }

    #[Test]
    public function 失敗とブロックは理由のメモが必須(): void
    {
        $result = TestResult::factory()->create();

        foreach (['failed', 'blocked'] as $status) {
            $this->patchJson("/api/test-results/{$result->id}", ['status' => $status])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('note');
        }

        $this->patchJson("/api/test-results/{$result->id}", ['status' => 'failed', 'note' => 'エラー画面が表示された'])
            ->assertOk()
            ->assertJsonPath('data.note', 'エラー画面が表示された');
    }

    #[Test]
    public function 未実施に戻すと実施日時がクリアされる(): void
    {
        $result = TestResult::factory()->create(['status' => 'passed', 'executed_at' => now()]);

        $this->patchJson("/api/test-results/{$result->id}", ['status' => 'untested'])
            ->assertOk()
            ->assertJsonPath('data.executed_at', null);
    }

    #[Test]
    public function 定義されていないステータスは422を返す(): void
    {
        $result = TestResult::factory()->create();

        $this->patchJson("/api/test-results/{$result->id}", ['status' => 'done'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }
}
