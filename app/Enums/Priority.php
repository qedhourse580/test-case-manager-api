<?php

namespace App\Enums;

/**
 * テストケースの優先度
 */
enum Priority: string
{
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
}
