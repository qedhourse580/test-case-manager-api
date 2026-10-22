<?php

namespace Database\Factories;

use App\Enums\ResultStatus;
use App\Models\TestCase;
use App\Models\TestResult;
use App\Models\TestRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TestResult> */
class TestResultFactory extends Factory
{
    protected $model = TestResult::class;

    public function definition(): array
    {
        return [
            'test_run_id' => TestRun::factory(),
            'test_case_id' => fn (array $attributes) => TestCase::factory()->create([
                'project_id' => TestRun::find($attributes['test_run_id'])->project_id,
            ])->id,
            'status' => ResultStatus::Untested,
        ];
    }
}
