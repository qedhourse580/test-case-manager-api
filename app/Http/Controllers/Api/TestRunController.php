<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTestRunRequest;
use App\Http\Resources\TestRunResource;
use App\Models\Project;
use App\Models\TestRun;
use App\Services\TestRunService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TestRunController extends Controller
{
    public function __construct(private readonly TestRunService $service) {}

    public function index(Project $project): AnonymousResourceCollection
    {
        $runs = $project->testRuns()->withCount('results')->latest('id')->paginate(20);

        return TestRunResource::collection($runs);
    }

    public function store(StoreTestRunRequest $request, Project $project): JsonResponse
    {
        $run = $this->service->create($project, $request->validated());

        return TestRunResource::make($run->loadCount('results'))->response()->setStatusCode(201);
    }

    public function show(TestRun $testRun): TestRunResource
    {
        return TestRunResource::make($testRun->load('results.testCase')->loadCount('results'));
    }

    public function destroy(TestRun $testRun): Response
    {
        $testRun->delete();

        return response()->noContent();
    }

    /**
     * 進捗率・合格率などの集計
     */
    public function summary(TestRun $testRun): JsonResponse
    {
        return response()->json(['data' => $this->service->summarize($testRun)]);
    }
}
