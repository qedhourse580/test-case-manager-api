<?php

namespace App\Enums;

/**
 * 不具合の重要度
 */
enum Severity: string
{
    case Critical = 'critical';
    case Major = 'major';
    case Minor = 'minor';
    case Trivial = 'trivial';
}
