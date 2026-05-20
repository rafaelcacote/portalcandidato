<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Admin\StoreProcessTitleGroupRequest;
use App\Http\Requests\Modules\Admin\UpdateProcessTitleGroupRequest;
use App\Models\Modules\Admin\Models\ProcessTitleGroup;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Support\InertiaToast;
use Illuminate\Http\RedirectResponse;

class ProcessTitleGroupController extends Controller
{
    public function store(
        StoreProcessTitleGroupRequest $request,
        SelectionProcess $selectionProcess
    ): RedirectResponse {
        $validated = $request->validated();

        $selectionProcess->titleGroups()->create([
            'code' => strtoupper(trim($validated['code'])),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'max_score' => $validated['max_score'],
            'order' => $validated['order'] ?? 0,
            'is_active' => true,
        ]);

        InertiaToast::success('Grupo de títulos adicionado ao processo.');

        return back();
    }

    public function update(
        UpdateProcessTitleGroupRequest $request,
        SelectionProcess $selectionProcess,
        ProcessTitleGroup $titleGroup
    ): RedirectResponse {
        abort_unless($titleGroup->selection_process_id === $selectionProcess->id, 404);

        $validated = $request->validated();

        $titleGroup->update([
            'code' => strtoupper(trim($validated['code'])),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'max_score' => $validated['max_score'],
            'order' => $validated['order'] ?? $titleGroup->order,
        ]);

        InertiaToast::success('Grupo de títulos atualizado com sucesso.');

        return back();
    }

    public function destroy(
        SelectionProcess $selectionProcess,
        ProcessTitleGroup $titleGroup
    ): RedirectResponse {
        abort_unless($titleGroup->selection_process_id === $selectionProcess->id, 404);

        $titleGroup->delete();

        InertiaToast::success('Grupo de títulos removido do processo.');

        return back();
    }
}
