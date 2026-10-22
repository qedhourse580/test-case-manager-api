<?php

namespace App\Models;

use App\Enums\Priority;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'preconditions',
        'steps',
        'expected_result',
        'priority',
        'is_active',
    ];

    protected $attributes = [
        'priority' => 'medium',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'priority' => Priority::class,
            'is_active' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(TestResult::class);
    }

    /**
     * 有効なテストケースだけに絞り込む
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
