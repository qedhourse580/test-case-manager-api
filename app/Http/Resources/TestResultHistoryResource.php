<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TestResultHistory */
class TestResultHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'note' => $this->note,
            'executed_at' => $this->executed_at?->toIso8601String(),
            'recorded_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
