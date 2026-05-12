<?php

use App\Http\Controllers\Modules\Candidate\ApplicationController;
use App\Http\Controllers\Modules\Candidate\ApplicationWizardController;
use App\Http\Controllers\Modules\Candidate\CandidateDashboardController;
use App\Http\Controllers\Modules\Candidate\DocumentController;
use App\Http\Controllers\Modules\Candidate\ProcessBrowseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:candidato'])
    ->prefix('candidato')
    ->name('candidate.')
    ->group(function (): void {
        Route::get('dashboard', CandidateDashboardController::class)->name('dashboard');
        Route::get('processes', [ProcessBrowseController::class, 'index'])->name('processes.index');
        Route::get('processes/{selectionProcess}', [ProcessBrowseController::class, 'show'])->name('processes.show');
        Route::get('applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::get('applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
        Route::post('processes/{selectionProcess}/applications/start', [ApplicationWizardController::class, 'start'])->name('applications.start');
        Route::post('applications/{application}/steps/{step}', [ApplicationWizardController::class, 'storeStep'])->name('applications.step.store');
        Route::post('applications/{application}/submit', [ApplicationWizardController::class, 'submit'])->name('applications.submit');
        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::post('applications/{application}/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::delete('applications/{application}/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    });
