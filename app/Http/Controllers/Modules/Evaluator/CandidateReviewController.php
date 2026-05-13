<?php

namespace App\Http\Controllers\Modules\Evaluator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Evaluator\DecideApplicationDocumentRequest;
use App\Mail\DocumentoRecusado;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Modules\Shared\Enums\ApplicationStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateReviewController extends Controller
{
    public function show(Application $application): Response
    {
        $application->load([
            'user',
            'selectionProcess.criteria',
            'documents' => fn ($q) => $q
                ->with(['requiredDocument', 'titleItem.titleGroup'])
                ->orderBy('id'),
            'evaluations' => fn ($q) => $q->where('evaluator_id', auth()->id())->with(['scores', 'documentScores']),
        ]);

        return Inertia::render('Evaluator/Candidates/Show', [
            'application' => $application,
        ]);
    }

    public function viewDocument(Application $application, ApplicationDocument $applicationDocument): StreamedResponse|HttpResponse
    {
        abort_if($applicationDocument->application_id !== $application->id, 422);
        abort_unless(Storage::exists($applicationDocument->caminho), 404);

        return Storage::response($applicationDocument->caminho, $applicationDocument->nome_arquivo);
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
