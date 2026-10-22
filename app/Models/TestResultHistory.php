<?php

namespace App\Models;

use App\Enums\ResultStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestResultHistory extends Model
{
    protected $fillable = ['status', 'note', 'executed_at'];

    protected function casts(): array
    {
        return [
            'status' => ResultStatus::class,
            'executed_at' => 'datetime',
        ];
    }

    public function testResult(): BelongsTo
    {
        return $this->belongsTo(TestResult::class);
    }
}
