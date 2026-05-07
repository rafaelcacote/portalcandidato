<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Admin\StoreEvaluatorAssignmentRequest;
use App\Models\Modules\Admin\Models\ProcessEvaluatorAssignment;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EvaluatorController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Evaluators/Index', [
            'assignments' => ProcessEvaluatorAssignment::query()
                ->with(['selectionProcess', 'evaluator'])
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Evaluators/Form', [
            'processes' => SelectionProcess::query()->select('id', 'titulo')->orderBy('titulo')->get(),
            'evaluators' => User::query()
                ->role('avaliador')
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreEvaluatorAssignmentRequest $request): RedirectResponse
    {
        ProcessEvaluatorAssignment::query()->firstOrCreate($request->validated());

        return redirect()
            ->route('admin.evaluators.index')
            ->with('success', 'Avaliador vinculado ao processo.');
    }
}
