<?php

namespace Tests\Unit;

use App\Enums\ResultStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ResultStatusTest extends TestCase
{
    #[Test]
    public function 未実施以外は実施済みとして扱う(): void
    {
        $this->assertFalse(ResultStatus::Untested->isExecuted());

        foreach ([ResultStatus::Passed, ResultStatus::Failed, ResultStatus::Blocked, ResultStatus::Skipped] as $status) {
            $this->assertTrue($status->isExecuted(), $status->value);
        }
    }
}
