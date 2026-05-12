<?php

namespace App\Http\Controllers\Modules\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Candidate/Applications/Index', [
            'applications' => Application::query()
                ->with('selectionProcess')
                ->where('user_id', $request->user()->id)
                ->latest()
                ->paginate(10),
        ]);
    }

    public function show(Application $application, Request $request): Response
    {
        abort_if($application->user_id !== $request->user()->id, 403);

        $application->load([
            'documents.requiredDocument',
            'documents.titleItem',
            'evaluations.evaluator',
            'selectionProcess' => function ($query): void {
                $query->with(array_merge(
                    ['requiredDocuments'],
                    SelectionProcess::candidateTitleCatalogEagerLoads(),
                ));
            },
        ]);

        return Inertia::render('Candidate/Applications/Show', [
            'application' => $application,
        ]);
    }
}
