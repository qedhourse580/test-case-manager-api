<?php

use App\Http\Controllers\Api\DefectController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\TestCaseController;
use App\Http\Controllers\Api\TestResultController;
use App\Http\Controllers\Api\TestRunController;
use Illuminate\Support\Facades\Route;

Route::apiResource('projects', ProjectController::class);

// セクション（シート → 大項目 → 中項目 → 小項目）
Route::apiResource('projects.sections', SectionController::class)->shallow();

// テストケース（一覧・作成はプロジェクト配下、それ以外は単独のURL）
Route::apiResource('projects.test-cases', TestCaseController::class)->shallow();

// テスト実行
Route::apiResource('projects.test-runs', TestRunController::class)
    ->shallow()
    ->except(['update']);
Route::get('test-runs/{test_run}/summary', [TestRunController::class, 'summary'])
    ->name('test-runs.summary');
Route::get('test-runs/{test_run}/sections-summary', [TestRunController::class, 'sectionsSummary'])
    ->name('test-runs.sections-summary');

// テスト結果の記録
Route::patch('test-results/{test_result}', [TestResultController::class, 'update'])
    ->name('test-results.update');
Route::get('test-results/{test_result}/history', [TestResultController::class, 'history'])
    ->name('test-results.history');

// 不具合
Route::get('projects/{project}/defects/summary', [DefectController::class, 'summary'])
    ->name('projects.defects.summary');
Route::apiResource('projects.defects', DefectController::class)->shallow();
Route::patch('defects/{defect}/status', [DefectController::class, 'changeStatus'])
    ->name('defects.status');
