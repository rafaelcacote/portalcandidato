<?php

namespace App\Http\Controllers\Modules\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProcessBrowseController extends Controller
{
    public function index(): Response
    {
        $processes = SelectionProcess::query()
            ->where('status', 'ativo')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Candidate/Processes/Index', [
            'processes' => $processes,
        ]);
    }

    public function show(SelectionProcess $selectionProcess, Request $request): Response
    {
        $selectionProcess->load(array_merge(
            ['stages', 'requiredDocuments', 'criteria'],
            SelectionProcess::candidateTitleCatalogEagerLoads(),
        ));

        $alreadyApplied = Application::query()
            ->where('selection_process_id', $selectionProcess->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        return Inertia::render('Candidate/Processes/Show', [
            'selectionProcess' => $selectionProcess,
            'alreadyApplied' => $alreadyApplied,
        ]);
    }
}
