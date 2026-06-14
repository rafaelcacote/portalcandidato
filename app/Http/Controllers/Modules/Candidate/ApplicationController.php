<?php

namespace App\Http\Controllers\Modules\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Modules\Admin\Services\SelectionProcessDocumentTemplateService;
use App\Modules\Candidate\Services\ApplicationAppealService;
use App\Modules\Candidate\Services\ApplicationProfessionalDocumentService;
use App\Modules\Candidate\Support\ResearchLineCatalog;
use App\Support\BrazilianStates;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    public function __construct(
        private readonly SelectionProcessDocumentTemplateService $documentTemplateService,
        private readonly ApplicationProfessionalDocumentService $professionalDocumentService,
        private readonly ApplicationAppealService $appealService,
    ) {}

    public function index(Request $request): Response
    {
        $applications = Application::query()
            ->with('selectionProcess')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        $applications->getCollection()->transform(function (Application $application): Application {
            $application->setAttribute(
                'comprovante_url',
                $application->isFinalizedForDocuments()
                    ? route('candidate.applications.documents.comprovante', $application)
                    : null,
            );

            return $application;
        });

        return Inertia::render('Candidate/Applications/Index', [
            'applications' => $applications,
        ]);
    }

    public function show(Application $application, Request $request): Response
    {
        abort_if($application->user_id !== $request->user()->id, 403);

        $application->load([
            'documents.requiredDocument',
            'documents.titleItem',
            'evaluations.evaluator',
            'appeals.processStage',
            'selectionProcess' => function ($query): void {
                $query->with(array_merge(
                    ['requiredDocuments.tipoDocumento', 'stages'],
                    SelectionProcess::candidateTitleCatalogEagerLoads(),
                ));
            },
        ]);

        $selectionProcess = $application->selectionProcess;

        if ($selectionProcess !== null) {
            $selectionProcess->setRelation(
                'requiredDocuments',
                $this->documentTemplateService->sortRequiredDocuments(
                    $selectionProcess->requiredDocuments,
                    $selectionProcess->tipo_programa,
                ),
            );
        }

        $user = $request->user();

        return Inertia::render('Candidate/Applications/Show', [
            'application' => $application,
            'ufs' => BrazilianStates::abbreviations(),
            'mustVerifyEmail' => $user instanceof MustVerifyEmail
                && $user->mustVerifyEmailAddress(),
            'professionalDocuments' => $this->professionalDocumentService->listForApplication($application),
            'appealStages' => $this->appealService->listStagesForApplication($application),
            'appeals' => $this->appealService->listAppealsForApplication($application),
            'hasOpenRecursoWindow' => $this->appealService->hasOpenRecursoWindow($application),
            'researchLineOptions' => ResearchLineCatalog::forFrontend(),
        ]);
    }
}
