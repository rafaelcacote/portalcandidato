<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Modules\Admin\Services\ReportPdfService;
use App\Modules\Shared\Enums\ApplicationStatus;
use App\Rules\Cpf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ReportController extends Controller
{
    public function __construct(private ReportPdfService $reportPdfService) {}

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

    public function show(SelectionProcess $selectionProcess, Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $candidates = $this->reportPdfService
            ->enrolledApplicationsQuery($selectionProcess)
            ->when(
                $search,
                fn ($query) => $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery
                        ->where('numero_protocolo', 'like', "%{$search}%")
                        ->orWhereHas(
                            'user',
                            fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"),
                        );
                }),
            )
            ->paginate(25)
            ->withQueryString()
            ->through(fn ($application): array => [
                'id' => $application->id,
                'numero_protocolo' => $application->numero_protocolo,
                'nome_completo' => $application->user?->name,
                'cpf_mascarado' => Cpf::maskForDisplay($application->user?->cpf),
            ]);

        return Inertia::render('Admin/Reports/Show', [
            'selectionProcess' => $selectionProcess->only('id', 'titulo', 'status'),
            'candidates' => $candidates,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function print(SelectionProcess $selectionProcess): SymfonyResponse
    {
        return $this->reportPdfService->inlineEnrolledCandidatesList($selectionProcess);
    }
}
