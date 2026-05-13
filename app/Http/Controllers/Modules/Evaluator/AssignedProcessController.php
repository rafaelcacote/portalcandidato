<?php

namespace App\Http\Controllers\Modules\Evaluator;

use App\Http\Controllers\Controller;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssignedProcessController extends Controller
{
    public function index(): Response
    {
        $evaluatorId = auth()->id();

        $processes = SelectionProcess::query()
            ->whereHas('evaluatorAssignments', fn ($query) => $query->where('user_id', $evaluatorId))
            ->withCount(['applications as total_candidates'])
            ->withCount([
                'applications as pending_candidates' => fn ($q) => $q
                    ->whereDoesntHave('evaluations', fn ($eq) => $eq->where('evaluator_id', $evaluatorId)->whereNotNull('resultado'))
                    ->whereIn('status', ['em_analise', 'enviada']),
            ])
            ->withCount([
                'applications as analyzed_candidates' => fn ($q) => $q
                    ->whereHas('evaluations', fn ($eq) => $eq->where('evaluator_id', $evaluatorId)->whereNotNull('resultado')),
            ])
            ->latest()
            ->paginate(12);

        return Inertia::render('Evaluator/Processes/Index', [
            'processes' => $processes,
        ]);
    }

    public function show(SelectionProcess $selectionProcess, Request $request): Response
    {
        $evaluatorId = auth()->id();
        $search = $request->string('search')->trim()->toString();
        $statusFilter = $request->string('status')->trim()->toString();

        $candidates = Application::query()
            ->where('selection_process_id', $selectionProcess->id)
            ->with(['user', 'evaluations' => fn ($q) => $q->where('evaluator_id', $evaluatorId)])
            ->when($search, fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")))
            ->when($statusFilter && $statusFilter !== 'all', fn ($q) => $q->where('status', $statusFilter))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $selectionProcess->load('criteria');

        return Inertia::render('Evaluator/Processes/Show', [
            'selectionProcess' => $selectionProcess->only('id', 'titulo', 'status', 'inscricao_inicio_em', 'inscricao_fim_em', 'criteria'),
            'candidates' => $candidates,
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
            ],
        ]);
    }
}
