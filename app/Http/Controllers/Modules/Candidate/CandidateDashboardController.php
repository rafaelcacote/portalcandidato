<?php

namespace App\Http\Controllers\Modules\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Modules\Shared\Enums\ApplicationStatus;
use App\Modules\Shared\Enums\DocumentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class CandidateDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $userId = $request->user()->id;

        $ongoingStatuses = [
            ApplicationStatus::Rascunho->value,
            ApplicationStatus::EmAnalise->value,
        ];

        $inscricoesEmAndamentoCount = Application::query()
            ->where('user_id', $userId)
            ->whereIn('status', $ongoingStatuses)
            ->count();

        $inscricoesEmAndamento = Application::query()
            ->where('user_id', $userId)
            ->whereIn('status', $ongoingStatuses)
            ->with('selectionProcess:id,titulo')
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Application $application): array => [
                'id' => $application->id,
                'status' => $application->status,
                'process_title' => $application->selectionProcess?->titulo ?? 'Processo',
                'numero_protocolo' => $application->numero_protocolo,
            ])
            ->all();

        $pendenciasInscricaoCount = Application::query()
            ->where('user_id', $userId)
            ->where('status', ApplicationStatus::Pendencia->value)
            ->count();

        $pendenciasInscricao = Application::query()
            ->where('user_id', $userId)
            ->where('status', ApplicationStatus::Pendencia->value)
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
            ->whereHas('application', fn ($query) => $query->where('user_id', $userId))
            ->count();

        $documentosRecusados = ApplicationDocument::query()
            ->where('status', DocumentStatus::Recusado->value)
            ->whereHas('application', fn ($query) => $query->where('user_id', $userId))
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

        return Inertia::render('Candidate/Dashboard', [
            'summary' => [
                'inscricoes_em_andamento' => $inscricoesEmAndamentoCount,
                'pendencias' => $pendenciasInscricaoCount + $documentosRecusadosCount,
                'mensagens_nao_lidas' => $mensagensNaoLidas,
            ],
            'inscricoes_em_andamento' => $inscricoesEmAndamento,
            'pendencias_inscricao' => $pendenciasInscricao,
            'documentos_recusados' => $documentosRecusados,
        ]);
    }
}
