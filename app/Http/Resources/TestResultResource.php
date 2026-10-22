<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TestResult */
class TestResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'test_run_id' => $this->test_run_id,
            'test_case_id' => $this->test_case_id,
            'test_case_title' => $this->whenLoaded('testCase', fn () => $this->testCase->title),
            'status' => $this->status->value,
            'note' => $this->note,
            'executed_at' => $this->executed_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
