<?php

namespace App\Http\Controllers\Modules\Evaluator;

use App\Http\Controllers\Controller;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Modules\Shared\Enums\ApplicationStatus;
use Inertia\Inertia;
use Inertia\Response;

class EvaluatorDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $evaluatorId = auth()->id();

        $assignedProcessIds = SelectionProcess::query()
            ->whereHas('evaluatorAssignments', fn ($q) => $q->where('user_id', $evaluatorId))
            ->pluck('id');

        $totalProcesses = $assignedProcessIds->count();

        $totalCandidates = Application::query()
            ->whereIn('selection_process_id', $assignedProcessIds)
            ->count();

        $pendingAnalysis = Application::query()
            ->whereIn('selection_process_id', $assignedProcessIds)
            ->whereDoesntHave('evaluations', fn ($q) => $q->where('evaluator_id', $evaluatorId))
            ->whereIn('status', [
                ApplicationStatus::EmAnalise->value,
                ApplicationStatus::Inscrita->value,
            ])
            ->count();

        $analysisCompleted = Application::query()
            ->whereIn('selection_process_id', $assignedProcessIds)
            ->whereHas('evaluations', fn ($q) => $q->where('evaluator_id', $evaluatorId)->whereNotNull('resultado'))
            ->count();

        $recentProcesses = SelectionProcess::query()
            ->whereHas('evaluatorAssignments', fn ($q) => $q->where('user_id', $evaluatorId))
            ->withCount(['applications as total_candidates'])
            ->withCount([
                'applications as pending_candidates' => fn ($q) => $q
                    ->whereDoesntHave('evaluations', fn ($eq) => $eq->where('evaluator_id', $evaluatorId)->whereNotNull('resultado'))
                    ->whereIn('status', [
                        ApplicationStatus::EmAnalise->value,
                        ApplicationStatus::Inscrita->value,
                    ]),
            ])
            ->latest()
            ->limit(5)
            ->get(['id', 'titulo', 'status', 'inscricao_inicio_em', 'inscricao_fim_em']);

        return Inertia::render('Evaluator/Dashboard', [
            'stats' => [
                'processes_total' => $totalProcesses,
                'candidates_total' => $totalCandidates,
                'pending_analysis' => $pendingAnalysis,
                'analysis_completed' => $analysisCompleted,
            ],
            'recent_processes' => $recentProcesses,
        ]);
    }
}
