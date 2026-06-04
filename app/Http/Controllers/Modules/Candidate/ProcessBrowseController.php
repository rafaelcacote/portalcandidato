<?php

namespace App\Http\Controllers\Modules\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Modules\Admin\Services\SelectionProcessDocumentTemplateService;
use App\Modules\Shared\Enums\ApplicationStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProcessBrowseController extends Controller
{
    public function __construct(
        private readonly SelectionProcessDocumentTemplateService $documentTemplateService,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $tipoPrograma = $request->string('tipo_programa')->trim()->value();

        $filteredQuery = SelectionProcess::query()
            ->where('status', 'ativo')
            ->when($search !== '', fn ($q) => $q->where('titulo', 'like', "%{$search}%"))
            ->when($tipoPrograma !== '', fn ($q) => $q->where('tipo_programa', $tipoPrograma));

        $openEnrollmentCount = (clone $filteredQuery)->inscricaoAberta()->count();

        $processes = (clone $filteredQuery)
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $draftApplicationIdsByProcessId = [];
        if ($processes->isNotEmpty()) {
            $draftApplicationIdsByProcessId = Application::query()
                ->where('user_id', auth()->id())
                ->whereIn('selection_process_id', $processes->pluck('id'))
                ->where('status', ApplicationStatus::Rascunho->value)
                ->pluck('id', 'selection_process_id')
                ->all();
        }

        return Inertia::render('Candidate/Processes/Index', [
            'processes' => $processes,
            'openEnrollmentCount' => $openEnrollmentCount,
            'draftApplicationIdsByProcessId' => $draftApplicationIdsByProcessId,
            'filters' => [
                'search' => $search,
                'tipo_programa' => $tipoPrograma,
            ],
        ]);
    }

    public function show(SelectionProcess $selectionProcess, Request $request): Response
    {
        $selectionProcess->load(array_merge(
            ['stages', 'requiredDocuments.tipoDocumento', 'criteria'],
            SelectionProcess::candidateTitleCatalogEagerLoads(),
        ));

        $selectionProcess->setRelation(
            'requiredDocuments',
            $this->documentTemplateService->sortRequiredDocuments(
                $selectionProcess->requiredDocuments,
                $selectionProcess->tipo_programa,
            ),
        );

        $application = Application::query()
            ->where('selection_process_id', $selectionProcess->id)
            ->where('user_id', $request->user()->id)
            ->first();

        $draftApplicationId = null;
        $alreadyApplied = false;
        if ($application !== null) {
            if ($application->status === ApplicationStatus::Rascunho->value) {
                $draftApplicationId = $application->id;
            } else {
                $alreadyApplied = true;
            }
        }

        return Inertia::render('Candidate/Processes/Show', [
            'selectionProcess' => $selectionProcess,
            'alreadyApplied' => $alreadyApplied,
            'draftApplicationId' => $draftApplicationId,
        ]);
    }
}
