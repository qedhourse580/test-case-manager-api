<?php

namespace Database\Factories;

use App\Enums\DefectStatus;
use App\Enums\Severity;
use App\Models\Defect;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Defect> */
class DefectFactory extends Factory
{
    protected $model = Defect::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'steps_to_reproduce' => "1. ".fake()->sentence(),
            'severity' => fake()->randomElement(Severity::cases()),
            'status' => DefectStatus::Open,
        ];
    }

    public function status(DefectStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
