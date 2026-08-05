<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Admin\FilterCandidateContactsReportRequest;
use App\Http\Requests\Modules\Admin\FilterEnrolledCandidatesReportRequest;
use App\Http\Requests\Modules\Admin\FilterEvaluatedCandidatesReportRequest;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Modules\Admin\Services\CandidateContactsReportService;
use App\Modules\Admin\Services\EvaluatedCandidatesReportService;
use App\Modules\Admin\Services\ReportPdfService;
use App\Modules\Candidate\Support\ResearchLineCatalog;
use App\Modules\Shared\Enums\ApplicationStatus;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ReportController extends Controller
{
    public function __construct(
        private ReportPdfService $reportPdfService,
        private EvaluatedCandidatesReportService $evaluatedCandidatesReportService,
        private CandidateContactsReportService $candidateContactsReportService,
    ) {}

    public function index(): Response
    {
        $processes = SelectionProcess::query()
            ->select('id', 'titulo', 'status', 'inscricao_inicio_em', 'inscricao_fim_em')
            ->withCount([
                'applications as enrolled_candidates_count' => fn ($query) => $query
                    ->whereNotNull('finalizada_em')
                    ->whereNotNull('numero_protocolo')
                    ->where('status', '!=', ApplicationStatus::Rascunho->value),
            ])
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Admin/Reports/Index', [
            'processes' => $processes,
        ]);
    }

    public function evaluated(FilterEvaluatedCandidatesReportRequest $request): Response
    {
        $filters = $request->filters();

        $candidates = $this->evaluatedCandidatesReportService
            ->query($filters)
            ->paginate(25)
            ->withQueryString()
            ->through(fn ($application): array => $this->evaluatedCandidatesReportService->mapApplication($application));

        return Inertia::render('Admin/Reports/Evaluated', [
            'candidates' => $candidates,
            'filters' => $filters,
            'filterOptions' => $this->evaluatedCandidatesReportService->filterOptions(
                $filters['selection_process_id'],
            ),
        ]);
    }

    public function printEvaluated(FilterEvaluatedCandidatesReportRequest $request): SymfonyResponse
    {
        return $this->evaluatedCandidatesReportService->inlineEvaluatedCandidatesList(
            $request->filters(),
        );
    }

    public function contacts(FilterCandidateContactsReportRequest $request): Response
    {
        $filters = $request->filters();

        $candidates = $this->candidateContactsReportService
            ->query($filters)
            ->paginate(25)
            ->withQueryString()
            ->through(fn ($application): array => $this->candidateContactsReportService->mapApplication($application));

        return Inertia::render('Admin/Reports/Contacts', [
            'candidates' => $candidates,
            'filters' => $filters,
            'filterOptions' => $this->candidateContactsReportService->filterOptions(),
        ]);
    }

    public function printContacts(FilterCandidateContactsReportRequest $request): SymfonyResponse
    {
        return $this->candidateContactsReportService->inlineContactsList(
            $request->filters(),
        );
    }

    public function show(
        SelectionProcess $selectionProcess,
        FilterEnrolledCandidatesReportRequest $request,
    ): Response {
        $filters = $request->filters();

        $candidates = $this->reportPdfService
            ->enrolledApplicationsQuery($selectionProcess, $filters)
            ->paginate(25)
            ->withQueryString()
            ->through(fn ($application): array => [
                'id' => $application->id,
                ...$this->reportPdfService->mapApplicationForReport($application),
            ]);

        return Inertia::render('Admin/Reports/Show', [
            'selectionProcess' => $selectionProcess->only('id', 'titulo', 'status'),
            'candidates' => $candidates,
            'filters' => $filters,
            'filterOptions' => $this->filterOptions($selectionProcess),
        ]);
    }

    public function print(
        SelectionProcess $selectionProcess,
        FilterEnrolledCandidatesReportRequest $request,
    ): SymfonyResponse {
        return $this->reportPdfService->inlineEnrolledCandidatesList(
            $selectionProcess,
            $request->filters(),
        );
    }

    /**
     * @return array{
     *     pcd: list<array{value: string, label: string}>,
     *     vinculo: list<array{value: string, label: string}>,
     *     status: list<array{value: string, label: string}>,
     *     researchLines: array{
     *         lines: list<array{value: string, label: string}>,
     *         advisors: array<string, list<string>>
     *     }
     * }
     */
    private function filterOptions(SelectionProcess $selectionProcess): array
    {
        return [
            'pcd' => [
                ['value' => 'all', 'label' => 'Todos'],
                ['value' => 'sim', 'label' => 'Sim'],
                ['value' => 'nao', 'label' => 'Não'],
            ],
            'vinculo' => [
                ['value' => 'all', 'label' => 'Todos'],
                ['value' => 'sem_vinculo', 'label' => 'Sem vínculo empregatício'],
                ['value' => 'com_vinculo', 'label' => 'Com vínculo empregatício'],
            ],
            'status' => [
                ['value' => 'all', 'label' => 'Todos'],
                ...collect(ApplicationStatus::cases())
                    ->reject(fn (ApplicationStatus $status): bool => $status === ApplicationStatus::Rascunho)
                    ->map(fn (ApplicationStatus $status): array => [
                        'value' => $status->value,
                        'label' => $this->statusLabel($status),
                    ])
                    ->values()
                    ->all(),
            ],
            'researchLines' => ResearchLineCatalog::forFrontend($selectionProcess->id),
        ];
    }

    private function statusLabel(ApplicationStatus $status): string
    {
        return match ($status) {
            ApplicationStatus::Inscrita => 'Inscrita',
            ApplicationStatus::EmAnalise => 'Em análise',
            ApplicationStatus::Pendencia => 'Pendência',
            ApplicationStatus::Aprovada => 'Aprovada',
            ApplicationStatus::Reprovada => 'Reprovada',
            ApplicationStatus::Cancelada => 'Cancelada',
            ApplicationStatus::Rascunho => 'Rascunho',
        };
    }
}
