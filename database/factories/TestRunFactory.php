<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\TestRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TestRun> */
class TestRunFactory extends Factory
{
    protected $model = TestRun::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => 'Sprint '.fake()->numberBetween(1, 50).' 回帰テスト',
            'environment' => fake()->randomElement(['staging', 'dev', 'production']),
        ];
    }
}
