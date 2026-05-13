<?php

use App\Http\Controllers\Modules\Evaluator\AssignedProcessController;
use App\Http\Controllers\Modules\Evaluator\CandidateReviewController;
use App\Http\Controllers\Modules\Evaluator\EvaluatorDashboardController;
use App\Http\Controllers\Modules\Evaluator\ScoringController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:avaliador'])
    ->prefix('avaliador')
    ->name('evaluator.')
    ->group(function (): void {
        Route::get('dashboard', EvaluatorDashboardController::class)->name('dashboard');
        Route::get('processes', [AssignedProcessController::class, 'index'])->name('processes.index');
        Route::get('processes/{selectionProcess}', [AssignedProcessController::class, 'show'])->name('processes.show');
        Route::get('candidates/{application}', [CandidateReviewController::class, 'show'])->name('candidates.show');
        Route::get('candidates/{application}/documents/{applicationDocument}/view', [CandidateReviewController::class, 'viewDocument'])->name('candidates.documents.view');
        Route::post('candidates/{application}/documents/{applicationDocument}/decision', [CandidateReviewController::class, 'decideDocument'])->name('candidates.documents.decision');
        Route::post('candidates/{application}/score', [ScoringController::class, 'store'])->name('candidates.score.store');
    });
