<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Admin\StoreProcessStageRequest;
use App\Models\Modules\Admin\Models\ProcessStage;
use App\Models\Modules\Admin\Models\SelectionProcess;
use Illuminate\Http\RedirectResponse;

class ProcessStageController extends Controller
{
    public function store(
        StoreProcessStageRequest $request,
        SelectionProcess $selectionProcess
    ): RedirectResponse {
        $selectionProcess->stages()->create($request->validated());

        return back()->with('success', 'Etapa adicionada com sucesso.');
    }

    public function destroy(
        SelectionProcess $selectionProcess,
        ProcessStage $processStage
    ): RedirectResponse {
        abort_unless($processStage->selection_process_id === $selectionProcess->id, 404);

        $processStage->delete();

        return back()->with('success', 'Etapa removida com sucesso.');
    }
}
