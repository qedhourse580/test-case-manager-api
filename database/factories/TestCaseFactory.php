<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Models\Project;
use App\Models\TestCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TestCase> */
class TestCaseFactory extends Factory
{
    protected $model = TestCase::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'preconditions' => fake()->sentence(),
            'steps' => "1. ".fake()->sentence()."\n2. ".fake()->sentence(),
            'expected_result' => fake()->sentence(),
            'priority' => fake()->randomElement(Priority::cases()),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
