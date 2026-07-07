<?php

namespace App\Http\Controllers\Modules\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Modules\Candidate\Services\EnrollmentFinalizeReminderService;
use App\Modules\Shared\Enums\ApplicationStatus;
use App\Modules\Shared\Enums\DocumentStatus;
use App\Support\InertiaToast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class CandidateDashboardController extends Controller
{
    public function __construct(
        private readonly EnrollmentFinalizeReminderService $enrollmentFinalizeReminder,
    ) {}

    public function __invoke(Request $request): Response
    {
        $userId = $request->user()->id;

        if (
            $this->enrollmentFinalizeReminder->hasDraftEnrollment($userId)
            && ! $request->session()->get(EnrollmentFinalizeReminderService::SESSION_SHOWN_KEY)
        ) {
            InertiaToast::warning(
                $this->enrollmentFinalizeReminder->entryMessage(),
                EnrollmentFinalizeReminderService::TOAST_LIFE_MS,
            );
            $request->session()->put(EnrollmentFinalizeReminderService::SESSION_SHOWN_KEY, true);
        }

        $ongoingStatuses = [
            ApplicationStatus::Rascunho->value,
            ApplicationStatus::EmAnalise->value,
        ];

        $ongoingApplications = Application::query()
            ->where('user_id', $userId)
            ->whereIn('status', $ongoingStatuses)
            ->with('selectionProcess:id,titulo,status,inscricao_inicio_em,inscricao_fim_em')
            ->latest('updated_at')
            ->get();

        $mapOngoingApplication = fn (Application $application): array => [
            'id' => $application->id,
            'status' => $application->status,
            'process_title' => $application->selectionProcess?->titulo ?? 'Processo',
            'numero_protocolo' => $application->numero_protocolo,
            'inscricao_aberta' => $application->canModifyEnrollment(),
        ];

        $allOngoingApplications = $ongoingApplications
            ->map($mapOngoingApplication)
            ->all();

        $actionableOngoingApplications = $ongoingApplications
            ->filter(fn (Application $application): bool => $application->countsAsOngoingEnrollment());

        $inscricoesEmAndamentoCount = $actionableOngoingApplications->count();

        $inscricoesEmAndamento = $actionableOngoingApplications
            ->take(5)
            ->map($mapOngoingApplication)
            ->values()
            ->all();

        $pendenciasInscricaoCount = Application::query()
            ->where('user_id', $userId)
            ->where('status', ApplicationStatus::Pendencia->value)
            ->whereNull('finalizada_em')
            ->count();

        $pendenciasInscricao = Application::query()
            ->where('user_id', $userId)
            ->where('status', ApplicationStatus::Pendencia->value)
            ->whereNull('finalizada_em')
            ->with('selectionProcess:id,titulo')
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Application $application): array => [
                'id' => $application->id,
                'process_title' => $application->selectionProcess?->titulo ?? 'Processo',
                'numero_protocolo' => $application->numero_protocolo,
            ])
            ->all();

        $documentosRecusadosCount = ApplicationDocument::query()
            ->where('status', DocumentStatus::Recusado->value)
            ->whereHas('application', fn ($query) => $query
                ->where('user_id', $userId)
                ->whereNull('finalizada_em'))
            ->count();

        $documentosRecusados = ApplicationDocument::query()
            ->where('status', DocumentStatus::Recusado->value)
            ->whereHas('application', fn ($query) => $query
                ->where('user_id', $userId)
                ->whereNull('finalizada_em'))
            ->with([
                'application.selectionProcess:id,titulo',
                'requiredDocument:id,nome',
            ])
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (ApplicationDocument $document): array => [
                'id' => $document->id,
                'application_id' => $document->application_id,
                'nome_arquivo' => $document->nome_arquivo,
                'tipo_documento' => $document->requiredDocument?->nome ?? 'Documento',
                'process_title' => $document->application?->selectionProcess?->titulo ?? 'Processo',
                'motivo_recusa' => $document->motivo_recusa,
            ])
            ->all();

        $mensagensNaoLidas = Schema::hasTable('notifications')
            ? $request->user()->unreadNotifications()->count()
            : 0;

        $highlightApplication = null;

        if ($pendenciasInscricao !== []) {
            $first = $pendenciasInscricao[0];
            $highlightApplication = [
                'id' => $first['id'],
                'process_title' => $first['process_title'],
                'status' => ApplicationStatus::Pendencia->value,
                'numero_protocolo' => $first['numero_protocolo'],
                'kind' => 'pendencia',
                'detail' => null,
            ];
        } elseif ($documentosRecusados !== []) {
            $first = $documentosRecusados[0];
            $highlightApplication = [
                'id' => $first['application_id'],
                'process_title' => $first['process_title'],
                'status' => ApplicationStatus::Pendencia->value,
                'numero_protocolo' => null,
                'kind' => 'documento_recusado',
                'detail' => $first['motivo_recusa'],
            ];
        } elseif ($allOngoingApplications !== []) {
            $first = collect($allOngoingApplications)->first(
                fn (array $row): bool => $row['status'] === ApplicationStatus::Rascunho->value
                    && ($row['inscricao_aberta'] ?? true),
            ) ?? collect($allOngoingApplications)->first(
                fn (array $row): bool => $row['inscricao_aberta'] ?? true,
            ) ?? $allOngoingApplications[0];
            $highlightApplication = [
                'id' => $first['id'],
                'process_title' => $first['process_title'],
                'status' => $first['status'],
                'numero_protocolo' => $first['numero_protocolo'],
                'kind' => ($first['inscricao_aberta'] ?? true) ? 'rascunho' : 'inscricao_encerrada',
                'inscricao_aberta' => $first['inscricao_aberta'] ?? true,
                'detail' => null,
            ];
        }

        return Inertia::render('Candidate/Dashboard', [
            'summary' => [
                'inscricoes_em_andamento' => $inscricoesEmAndamentoCount,
                'pendencias' => $pendenciasInscricaoCount + $documentosRecusadosCount,
                'mensagens_nao_lidas' => $mensagensNaoLidas,
            ],
            'inscricoes_em_andamento' => $inscricoesEmAndamento,
            'pendencias_inscricao' => $pendenciasInscricao,
            'documentos_recusados' => $documentosRecusados,
            'highlight_application' => $highlightApplication,
        ]);
    }
}
