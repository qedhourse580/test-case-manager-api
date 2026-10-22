<?php

namespace App\Models;

use App\Enums\ResultStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestResult extends Model
{
    use HasFactory;

    protected $fillable = ['status', 'note', 'executed_at'];

    protected $attributes = [
        'status' => 'untested',
    ];

    protected function casts(): array
    {
        return [
            'status' => ResultStatus::class,
            'executed_at' => 'datetime',
        ];
    }

    public function testRun(): BelongsTo
    {
        return $this->belongsTo(TestRun::class);
    }

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(TestCase::class);
    }

    /**
     * 記録の履歴（古い順）
     */
    public function histories(): HasMany
    {
        return $this->hasMany(TestResultHistory::class)->orderBy('id');
    }

    public function defects(): HasMany
    {
        return $this->hasMany(Defect::class);
    }
}
