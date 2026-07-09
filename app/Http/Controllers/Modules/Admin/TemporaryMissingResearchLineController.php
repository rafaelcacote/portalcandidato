<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Admin\UpdateMissingResearchLineRequest;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Modules\Admin\Services\MissingResearchLineBackfillService;
use App\Modules\Candidate\Support\ResearchLineCatalog;
use App\Support\InertiaToast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * TEMPORÁRIO — remover após preencher linhas de pesquisa dos candidatos legados.
 */
class TemporaryMissingResearchLineController extends Controller
{
    public function __construct(
        private readonly MissingResearchLineBackfillService $missingResearchLineBackfillService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Temporary/MissingResearchLines/Index', [
            'processes' => $this->missingResearchLineBackfillService->processesWithMissingCounts(),
        ]);
    }

    public function show(SelectionProcess $selectionProcess): Response
    {
        $applications = $this->missingResearchLineBackfillService
            ->applicationsMissingResearchLine($selectionProcess->id)
            ->map(fn (Application $application): array => $this->missingResearchLineBackfillService
                ->serializeApplication($application))
            ->all();

        return Inertia::render('Admin/Temporary/MissingResearchLines/Show', [
            'selectionProcess' => $selectionProcess->only('id', 'titulo', 'status'),
            'applications' => $applications,
            'researchLineOptions' => ResearchLineCatalog::forFrontend($selectionProcess->id),
        ]);
    }

    public function update(
        UpdateMissingResearchLineRequest $request,
        Application $application,
    ): RedirectResponse {
        if ($this->missingResearchLineBackfillService->hasResearchLine($application)) {
            InertiaToast::warning('Esta inscrição já possui linha de pesquisa definida.');

            return back();
        }

        $this->missingResearchLineBackfillService->updateResearchLine(
            $application,
            $request->validated('linha_pesquisa'),
            $request->validated('orientador'),
        );

        InertiaToast::success('Linha de pesquisa atualizada com sucesso.');

        return back();
    }
}
