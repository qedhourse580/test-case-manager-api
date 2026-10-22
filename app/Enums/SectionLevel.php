<?php

namespace App\Enums;

/**
 * セクションの階層（depth の値と対応）
 */
enum SectionLevel: int
{
    case Sheet = 1;
    case Large = 2;
    case Medium = 3;
    case Small = 4;

    public const MAX_DEPTH = 4;

    public function label(): string
    {
        return match ($this) {
            self::Sheet => 'シート',
            self::Large => '大項目',
            self::Medium => '中項目',
            self::Small => '小項目',
        };
    }
}
