<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSectionRequest;
use App\Http\Requests\UpdateSectionRequest;
use App\Http\Resources\SectionResource;
use App\Models\Project;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SectionController extends Controller
{
    /**
     * プロジェクトのセクションをツリー形式で返す（シート → 大項目 → 中項目 → 小項目）
     */
    public function index(Project $project): AnonymousResourceCollection
    {
        $withChildren = fn ($q) => $q->withCount('testCases');

        $sheets = $project->sections()
            ->whereNull('parent_id')
            ->withCount('testCases')
            ->with([
                'children' => $withChildren,
                'children.children' => $withChildren,
                'children.children.children' => $withChildren,
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return SectionResource::collection($sheets);
    }

    public function store(StoreSectionRequest $request, Project $project): JsonResponse
    {
        $parentId = $request->validated('parent_id');
        $depth = $parentId === null ? 1 : Section::findOrFail($parentId)->depth + 1;

        $section = new Section($request->safe()->only(['name', 'sort_order']));
        $section->project()->associate($project);
        $section->parent_id = $parentId;
        $section->depth = $depth;
        $section->save();

        return SectionResource::make($section->refresh())->response()->setStatusCode(201);
    }

    public function show(Section $section): SectionResource
    {
        return SectionResource::make($section->loadCount('testCases'));
    }

    public function update(UpdateSectionRequest $request, Section $section): SectionResource
    {
        $section->update($request->validated());

        return SectionResource::make($section);
    }

    /**
     * 削除すると下位のセクションも削除され、所属していたテストケースは「未分類」になる
     */
    public function destroy(Section $section): Response
    {
        $section->delete();

        return response()->noContent();
    }
}
