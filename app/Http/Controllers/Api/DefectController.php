<?php

namespace App\Http\Controllers\Api;

use App\Enums\DefectStatus;
use App\Enums\Severity;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeDefectStatusRequest;
use App\Http\Requests\StoreDefectRequest;
use App\Http\Requests\UpdateDefectRequest;
use App\Http\Resources\DefectResource;
use App\Models\Defect;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DefectController extends Controller
{
    /**
     * 不具合一覧（status・severity で絞り込み可能）
     */
    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $defects = $project->defects()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('severity'), fn ($q) => $q->where('severity', $request->string('severity')))
            ->latest('id')
            ->paginate(20);

        return DefectResource::collection($defects);
    }

    public function store(StoreDefectRequest $request, Project $project): JsonResponse
    {
        $defect = $project->defects()->create($request->validated());

        return DefectResource::make($defect->refresh())->response()->setStatusCode(201);
    }

    public function show(Defect $defect): DefectResource
    {
        return DefectResource::make($defect);
    }

    public function update(UpdateDefectRequest $request, Defect $defect): DefectResource
    {
        $defect->update($request->validated());

        return DefectResource::make($defect);
    }

    public function destroy(Defect $defect): Response
    {
        $defect->delete();

        return response()->noContent();
    }

    /**
     * ステータスを変更する（許可された遷移のみ）
     */
    public function changeStatus(ChangeDefectStatusRequest $request, Defect $defect): DefectResource
    {
        $defect->status = DefectStatus::from($request->validated('status'));
        $defect->save();

        return DefectResource::make($defect);
    }

    /**
     * プロジェクト内の不具合を、ステータス別・重要度別に集計する
     */
    public function summary(Project $project): JsonResponse
    {
        $byStatus = $project->defects()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $bySeverity = $project->defects()
            ->selectRaw('severity, COUNT(*) as aggregate')
            ->groupBy('severity')
            ->pluck('aggregate', 'severity');

        $unresolved = [DefectStatus::Open->value, DefectStatus::InProgress->value];

        return response()->json(['data' => [
            'project_id' => $project->id,
            'total' => (int) $byStatus->sum(),
            'unresolved' => (int) $byStatus->only($unresolved)->sum(),
            'by_status' => collect(DefectStatus::cases())
                ->mapWithKeys(fn ($s) => [$s->value => (int) ($byStatus[$s->value] ?? 0)]),
            'by_severity' => collect(Severity::cases())
                ->mapWithKeys(fn ($s) => [$s->value => (int) ($bySeverity[$s->value] ?? 0)]),
        ]]);
    }
}
