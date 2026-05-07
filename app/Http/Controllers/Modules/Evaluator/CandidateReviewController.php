<?php

namespace App\Http\Controllers\Modules\Evaluator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Evaluator\DecideApplicationDocumentRequest;
use App\Mail\DocumentoRecusado;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Modules\Shared\Enums\ApplicationStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class CandidateReviewController extends Controller
{
    public function show(Application $application): Response
    {
        $application->load(['user', 'selectionProcess.criteria', 'documents.requiredDocument', 'evaluations.scores']);

        return Inertia::render('Evaluator/Candidates/Show', [
            'application' => $application,
        ]);
    }

    public function decideDocument(
        Application $application,
        ApplicationDocument $applicationDocument,
        DecideApplicationDocumentRequest $request
    ): RedirectResponse {
        abort_if($applicationDocument->application_id !== $application->id, 422);

        $applicationDocument->update([
            'status' => $request->string('status')->toString(),
            'motivo_recusa' => $request->string('motivo_recusa')->toString() ?: null,
            'validado_por' => auth()->id(),
            'validado_em' => now(),
        ]);

        if ($applicationDocument->status === 'recusado') {
            $application->update(['status' => ApplicationStatus::Pendencia->value]);
            Mail::to($application->user)->queue(new DocumentoRecusado($applicationDocument));
        }

        return back()->with('success', 'Documento validado.');
    }
}
