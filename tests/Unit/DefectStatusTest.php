<?php

namespace Tests\Unit;

use App\Enums\DefectStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 不具合ステータスの状態遷移を、全組み合わせ（5×5）で確認する
 */
class DefectStatusTest extends TestCase
{
    /**
     * 許可する遷移の一覧（これ以外はすべて不可）
     */
    private const ALLOWED = [
        'open' => ['in_progress', 'rejected'],
        'in_progress' => ['resolved', 'open'],
        'resolved' => ['closed', 'open'],
        'rejected' => ['open'],
        'closed' => [],
    ];

    public static function transitionProvider(): iterable
    {
        foreach (DefectStatus::cases() as $from) {
            foreach (DefectStatus::cases() as $to) {
                $expected = in_array($to->value, self::ALLOWED[$from->value], true);
                yield "{$from->value} → {$to->value}" => [$from, $to, $expected];
            }
        }
    }

    #[Test]
    #[DataProvider('transitionProvider')]
    public function 状態遷移の可否が仕様どおりである(DefectStatus $from, DefectStatus $to, bool $expected): void
    {
        $this->assertSame($expected, $from->canTransitionTo($to));
    }

    #[Test]
    public function 同じステータスへの遷移は許可しない(): void
    {
        foreach (DefectStatus::cases() as $status) {
            $this->assertFalse($status->canTransitionTo($status), "{$status->value} → {$status->value}");
        }
    }
}
