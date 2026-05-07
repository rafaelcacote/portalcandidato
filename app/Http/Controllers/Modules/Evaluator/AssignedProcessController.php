<?php

namespace App\Http\Controllers\Modules\Evaluator;

use App\Http\Controllers\Controller;
use App\Models\Modules\Admin\Models\SelectionProcess;
use Inertia\Inertia;
use Inertia\Response;

class AssignedProcessController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Evaluator/Processes/Index', [
            'processes' => SelectionProcess::query()
                ->whereHas('evaluatorAssignments', fn ($query) => $query->where('user_id', auth()->id()))
                ->latest()
                ->paginate(10),
        ]);
    }

    public function show(SelectionProcess $selectionProcess): Response
    {
        $selectionProcess->load([
            'applications.user',
            'applications.documents',
            'criteria',
        ]);

        return Inertia::render('Evaluator/Processes/Show', [
            'selectionProcess' => $selectionProcess,
        ]);
    }
}
