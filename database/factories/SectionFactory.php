<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Section> */
class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'parent_id' => null,
            'name' => fake()->words(2, true),
            'depth' => 1,
            'sort_order' => 0,
        ];
    }

    /**
     * 指定したセクションの子として作る
     */
    public function childOf(Section $parent): static
    {
        return $this->state([
            'project_id' => $parent->project_id,
            'parent_id' => $parent->id,
            'depth' => $parent->depth + 1,
        ]);
    }
}
