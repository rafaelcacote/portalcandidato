<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Admin\UpdateApplicationAppealRequest;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\ApplicationAppeal;
use App\Modules\Admin\Services\ApplicationAppealAdminService;
use App\Modules\Shared\Enums\ApplicationAppealStatus;
use App\Support\InertiaToast;
use Illuminate\Http\RedirectResponse;

class ProcessApplicationAppealController extends Controller
{
    public function __construct(private readonly ApplicationAppealAdminService $appealAdminService) {}

    public function update(
        SelectionProcess $selectionProcess,
        ApplicationAppeal $applicationAppeal,
        UpdateApplicationAppealRequest $request,
    ): RedirectResponse {
        abort_unless(
            $applicationAppeal->application?->selection_process_id === $selectionProcess->id,
            404,
        );

        $validated = $request->validated();

        $this->appealAdminService->respond(
            $applicationAppeal,
            $validated['status'],
            $validated['resposta'] ?? null,
        );

        $statusLabel = ApplicationAppealStatus::tryFrom($validated['status'])?->label() ?? $validated['status'];
        InertiaToast::success("Recurso atualizado: {$statusLabel}.");

        return redirect()->route('admin.processes.show', $selectionProcess);
    }
}
