<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTestCaseRequest;
use App\Http\Requests\UpdateTestCaseRequest;
use App\Http\Resources\TestCaseResource;
use App\Models\Project;
use App\Models\TestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TestCaseController extends Controller
{
    /**
     * テストケース一覧（section_id・priority・is_active で絞り込み可能）
     */
    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $testCases = $project->testCases()
            ->when($request->filled('section_id'), fn ($q) => $q->where('section_id', $request->integer('section_id')))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->string('priority')))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('id')
            ->paginate(20);

        return TestCaseResource::collection($testCases);
    }

    public function store(StoreTestCaseRequest $request, Project $project): JsonResponse
    {
        $testCase = $project->testCases()->create($request->validated());

        return TestCaseResource::make($testCase->refresh())->response()->setStatusCode(201);
    }

    public function show(TestCase $testCase): TestCaseResource
    {
        return TestCaseResource::make($testCase);
    }

    public function update(UpdateTestCaseRequest $request, TestCase $testCase): TestCaseResource
    {
        $testCase->update($request->validated());

        return TestCaseResource::make($testCase);
    }

    public function destroy(TestCase $testCase): Response
    {
        $testCase->delete();

        return response()->noContent();
    }
}
