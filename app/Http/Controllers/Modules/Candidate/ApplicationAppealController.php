<?php

namespace App\Http\Controllers\Modules\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Candidate\StoreApplicationAppealRequest;
use App\Models\Modules\Candidate\Models\Application;
use App\Modules\Candidate\Services\ApplicationAppealService;
use App\Support\InertiaToast;
use Illuminate\Http\RedirectResponse;

class ApplicationAppealController extends Controller
{
    public function __construct(private readonly ApplicationAppealService $appealService) {}

    public function store(Application $application, StoreApplicationAppealRequest $request): RedirectResponse
    {
        abort_if($application->user_id !== auth()->id(), 403);

        $this->appealService->store(
            $application,
            (int) $request->validated('process_stage_id'),
            $request->validated('texto'),
        );

        InertiaToast::success('Recurso enviado com sucesso. Acompanhe o status nesta página.');

        return redirect()->route('candidate.applications.show', $application);
    }
}
