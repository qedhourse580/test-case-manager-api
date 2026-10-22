<?php

namespace App\Enums;

/**
 * 不具合のステータスと、許可する状態遷移
 *
 *   open ──▶ in_progress ──▶ resolved ──▶ closed
 *    │  ▲         │              │
 *    │  └─────────┘ (差し戻し)   └──▶ open (再オープン)
 *    ▼
 *   rejected ──▶ open (再オープン)
 */
enum DefectStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Rejected = 'rejected';

    /**
     * このステータスから遷移できるステータスの一覧
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Open => [self::InProgress, self::Rejected],
            self::InProgress => [self::Resolved, self::Open],
            self::Resolved => [self::Closed, self::Open],
            self::Rejected => [self::Open],
            self::Closed => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }
}
