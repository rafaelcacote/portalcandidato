<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use App\Modules\Shared\Enums\ApplicationStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $processesTotal = SelectionProcess::query()->count();
        $processesRascunho = SelectionProcess::query()->where('status', 'rascunho')->count();
        $processesAtivo = SelectionProcess::query()->where('status', 'ativo')->count();
        $processesEncerrado = SelectionProcess::query()->where('status', 'encerrado')->count();

        $applicationsTotal = Application::query()->count();
        $applicationsRascunho = Application::query()
            ->where('status', ApplicationStatus::Rascunho->value)
            ->count();
        $applicationsEmFluxo = Application::query()
            ->whereIn('status', [
                ApplicationStatus::EmAnalise->value,
                ApplicationStatus::Pendencia->value,
                ApplicationStatus::Inscrita->value,
            ])
            ->count();

        $evaluatorsTotal = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'avaliador'))
            ->count();

        $applicationsAprovada = Application::query()
            ->where('status', ApplicationStatus::Aprovada->value)
            ->count();

        $submittedForConversion = max(0, $applicationsTotal - $applicationsRascunho);
        $conversionPercent = $submittedForConversion > 0
            ? (int) round(($applicationsAprovada / $submittedForConversion) * 100)
            : 0;

        $dateSql = match (DB::getDriverName()) {
            'sqlite' => 'date(created_at)',
            'pgsql' => 'DATE(created_at)',
            default => 'DATE(created_at)',
        };

        $countsByDay = Application::query()
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw("{$dateSql} as day")
            ->selectRaw('COUNT(*) as c')
            ->groupBy('day')
            ->pluck('c', 'day');

        $applicationsTrend = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $applicationsTrend[] = (int) ($countsByDay[$day] ?? 0);
        }

        $highlightProcess = SelectionProcess::query()
            ->where('status', 'ativo')
            ->orderByDesc('updated_at')
            ->first();

        $highlightProcessPayload = $highlightProcess === null
            ? null
            : [
                'id' => $highlightProcess->id,
                'titulo' => $highlightProcess->titulo,
                'inscricao_inicio_em' => $highlightProcess->inscricao_inicio_em?->toIso8601String(),
                'inscricao_fim_em' => $highlightProcess->inscricao_fim_em?->toIso8601String(),
            ];
        $recentProcesses = SelectionProcess::query()
            ->latest('updated_at')
            ->limit(5)
            ->get(['id', 'titulo', 'status', 'inscricao_inicio_em', 'inscricao_fim_em'])
            ->map(fn (SelectionProcess $process): array => [
                'id' => $process->id,
                'titulo' => $process->titulo,
                'status' => $process->status,
                'inscricao_inicio_em' => $process->inscricao_inicio_em?->toIso8601String(),
                'inscricao_fim_em' => $process->inscricao_fim_em?->toIso8601String(),
            ])
            ->all();

        $recentApplications = Application::query()
            ->with([
                'selectionProcess:id,titulo',
                'user:id,name,email',
            ])
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Application $application): array => [
                'id' => $application->id,
                'status' => $application->status,
                'numero_protocolo' => $application->numero_protocolo,
                'process_title' => $application->selectionProcess?->titulo ?? '—',
                'candidate_name' => $application->user?->name ?? '—',
                'candidate_email' => $application->user?->email ?? '—',
                'updated_at' => $application->updated_at?->toIso8601String(),
            ])
            ->all();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'processes_total' => $processesTotal,
                'processes_rascunho' => $processesRascunho,
                'processes_ativo' => $processesAtivo,
                'processes_encerrado' => $processesEncerrado,
                'applications_total' => $applicationsTotal,
                'applications_rascunho' => $applicationsRascunho,
                'applications_em_fluxo' => $applicationsEmFluxo,
                'applications_aprovada' => $applicationsAprovada,
                'evaluators_total' => $evaluatorsTotal,
                'conversion_percent' => $conversionPercent,
            ],
            'applications_trend' => $applicationsTrend,
            'highlight_process' => $highlightProcessPayload,
            'recent_processes' => $recentProcesses,
            'recent_applications' => $recentApplications,
        ]);
    }
}
