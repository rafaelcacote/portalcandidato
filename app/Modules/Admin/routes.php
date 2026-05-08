<?php

use App\Http\Controllers\Modules\Admin\EvaluatorController;
use App\Http\Controllers\Modules\Admin\ProcessApplicationFieldController;
use App\Http\Controllers\Modules\Admin\ProcessCriteriaController;
use App\Http\Controllers\Modules\Admin\ProcessRequiredDocumentController;
use App\Http\Controllers\Modules\Admin\ProcessRequiredTituloController;
use App\Http\Controllers\Modules\Admin\ProcessTypeController;
use App\Http\Controllers\Modules\Admin\ReportController;
use App\Http\Controllers\Modules\Admin\SelectionProcessController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::inertia('dashboard', 'Admin/Dashboard')->name('dashboard');
        Route::get('processes/types', [ProcessTypeController::class, 'index'])
            ->name('processes.types.index');
        Route::get('support-tables/document-types/create', [ProcessTypeController::class, 'createDocumentType'])
            ->name('support-tables.document-types.create');
        Route::get('support-tables/document-types/{tipoDocumento}/edit', [ProcessTypeController::class, 'editDocumentType'])
            ->name('support-tables.document-types.edit');
        Route::get('support-tables/document-types', [ProcessTypeController::class, 'documentTypesPage'])
            ->name('support-tables.document-types.index');
        Route::get('support-tables/title-types/create', [ProcessTypeController::class, 'createTitleType'])
            ->name('support-tables.title-types.create');
        Route::get('support-tables/title-types/{tipoTitulo}/edit', [ProcessTypeController::class, 'editTitleType'])
            ->name('support-tables.title-types.edit');
        Route::get('support-tables/title-types', [ProcessTypeController::class, 'titleTypesPage'])
            ->name('support-tables.title-types.index');
        Route::post('processes/types/documentos', [ProcessTypeController::class, 'storeTipoDocumento'])
            ->name('processes.types.documentos.store');
        Route::put('processes/types/documentos/{tipoDocumento}', [ProcessTypeController::class, 'updateTipoDocumento'])
            ->name('processes.types.documentos.update');
        Route::delete('processes/types/documentos/{tipoDocumento}', [ProcessTypeController::class, 'destroyTipoDocumento'])
            ->name('processes.types.documentos.destroy');
        Route::post('processes/types/titulos', [ProcessTypeController::class, 'storeTipoTitulo'])
            ->name('processes.types.titulos.store');
        Route::put('processes/types/titulos/{tipoTitulo}', [ProcessTypeController::class, 'updateTipoTitulo'])
            ->name('processes.types.titulos.update');
        Route::delete('processes/types/titulos/{tipoTitulo}', [ProcessTypeController::class, 'destroyTipoTitulo'])
            ->name('processes.types.titulos.destroy');
        Route::resource('processes', SelectionProcessController::class)
            ->parameters(['processes' => 'selectionProcess']);
        Route::post('processes/{selectionProcess}/required-documents', [ProcessRequiredDocumentController::class, 'store'])
            ->name('processes.required-documents.store');
        Route::delete('processes/{selectionProcess}/required-documents/{processRequiredDocument}', [ProcessRequiredDocumentController::class, 'destroy'])
            ->name('processes.required-documents.destroy');
        Route::post('processes/{selectionProcess}/required-titulos', [ProcessRequiredTituloController::class, 'store'])
            ->name('processes.required-titulos.store');
        Route::delete('processes/{selectionProcess}/required-titulos/{processRequiredTitulo}', [ProcessRequiredTituloController::class, 'destroy'])
            ->name('processes.required-titulos.destroy');
        Route::post('processes/{selectionProcess}/criteria', [ProcessCriteriaController::class, 'store'])
            ->name('processes.criteria.store');
        Route::delete('processes/{selectionProcess}/criteria/{processCriteria}', [ProcessCriteriaController::class, 'destroy'])
            ->name('processes.criteria.destroy');
        Route::post('processes/{selectionProcess}/application-fields', [ProcessApplicationFieldController::class, 'store'])
            ->name('processes.application-fields.store');
        Route::delete('processes/{selectionProcess}/application-fields/{processApplicationField}', [ProcessApplicationFieldController::class, 'destroy'])
            ->name('processes.application-fields.destroy');
        Route::resource('evaluators', EvaluatorController::class)
            ->parameters(['evaluators' => 'evaluator'])
            ->except(['show']);
        Route::post('evaluators/{evaluator}/assignments', [EvaluatorController::class, 'storeAssignment'])
            ->name('evaluators.assignments.store');
        Route::put('evaluators/{evaluator}/assignments/{assignment}', [EvaluatorController::class, 'updateAssignment'])
            ->name('evaluators.assignments.update');
        Route::delete('evaluators/{evaluator}/assignments/{assignment}', [EvaluatorController::class, 'destroyAssignment'])
            ->name('evaluators.assignments.destroy');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    });
