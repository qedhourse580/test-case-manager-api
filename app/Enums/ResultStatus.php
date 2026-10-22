<?php

namespace App\Enums;

/**
 * テスト結果のステータス
 */
enum ResultStatus: string
{
    case Untested = 'untested';
    case Passed = 'passed';
    case Failed = 'failed';
    case Blocked = 'blocked';
    case Skipped = 'skipped';

    /**
     * 実施済み（未実施以外）かどうか
     */
    public function isExecuted(): bool
    {
        return $this !== self::Untested;
    }
}
