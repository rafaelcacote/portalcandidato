<?php

namespace App\Http\Controllers\Modules\Candidate;

use App\Http\Controllers\Controller;
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

        $application->load(['selectionProcess', 'documents.requiredDocument', 'evaluations.evaluator']);

        return Inertia::render('Candidate/Applications/Show', [
            'application' => $application,
        ]);
    }
}
