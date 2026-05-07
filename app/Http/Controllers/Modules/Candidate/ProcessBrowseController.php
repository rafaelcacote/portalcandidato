<?php

namespace App\Http\Controllers\Modules\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Modules\Admin\Models\SelectionProcess;
use Inertia\Inertia;
use Inertia\Response;

class ProcessBrowseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Candidate/Processes/Index', [
            'processes' => SelectionProcess::query()
                ->where('status', 'ativo')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function show(SelectionProcess $selectionProcess): Response
    {
        $selectionProcess->load(['stages', 'requiredDocuments', 'criteria']);

        return Inertia::render('Candidate/Processes/Show', [
            'selectionProcess' => $selectionProcess,
        ]);
    }
}
