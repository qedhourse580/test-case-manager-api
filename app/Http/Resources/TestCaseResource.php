<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TestCase */
class TestCaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'section_id' => $this->section_id,
            'title' => $this->title,
            'preconditions' => $this->preconditions,
            'steps' => $this->steps,
            'expected_result' => $this->expected_result,
            'priority' => $this->priority->value,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
