<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Admin\StoreProcessCriteriaRequest;
use App\Models\Modules\Admin\Models\ProcessCriteria;
use App\Models\Modules\Admin\Models\SelectionProcess;
use Illuminate\Http\RedirectResponse;

class ProcessCriteriaController extends Controller
{
    public function store(
        StoreProcessCriteriaRequest $request,
        SelectionProcess $selectionProcess
    ): RedirectResponse {
        $validated = $request->validated();

        $selectionProcess->criteria()->create($validated);

        return back()->with('success', 'Critério de pontuação adicionado com sucesso.');
    }

    public function destroy(
        SelectionProcess $selectionProcess,
        ProcessCriteria $processCriteria
    ): RedirectResponse {
        abort_unless($processCriteria->selection_process_id === $selectionProcess->id, 404);

        $processCriteria->delete();

        return back()->with('success', 'Critério de pontuação removido com sucesso.');
    }
}
