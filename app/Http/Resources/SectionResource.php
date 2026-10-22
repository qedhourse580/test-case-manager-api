<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Section */
class SectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'depth' => $this->depth,
            'level' => $this->level()->label(),
            'sort_order' => $this->sort_order,
            'test_cases_count' => $this->whenCounted('testCases'),
            'children' => self::collection($this->whenLoaded('children')),
        ];
    }
}
