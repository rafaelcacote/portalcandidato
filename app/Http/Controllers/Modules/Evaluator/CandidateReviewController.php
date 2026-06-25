<?php

namespace App\Http\Controllers\Modules\Evaluator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Evaluator\DecideApplicationDocumentRequest;
use App\Mail\DocumentoRecusado;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Modules\Candidate\Support\EmploymentRelationshipCatalog;
use App\Modules\Candidate\Support\ResearchLineCatalog;
use App\Modules\Evaluator\Services\DocumentValidationScoringService;
use App\Modules\Evaluator\Support\CandidatePhotoUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateReviewController extends Controller
{
    public function __construct(
        private readonly DocumentValidationScoringService $documentValidationScoringService,
    ) {}

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

        if ($application->user !== null) {
            $application->user->setAttribute(
                'photo_url',
                CandidatePhotoUrl::forApplication($application),
            );
        }

        $step2 = $application->dados_inscricao['step_2'] ?? null;
        $application->setAttribute(
            'employment_relationship_summary',
            EmploymentRelationshipCatalog::summaryFromStepData(is_array($step2) ? $step2 : null),
        );

        $step3 = $application->dados_inscricao['step_3'] ?? null;
        $application->setAttribute(
            'research_line_summary',
            ResearchLineCatalog::summaryFromStepData(
                is_array($step3) ? $step3 : null,
                $application->selection_process_id,
            ),
        );

        return Inertia::render('Evaluator/Candidates/Show', [
            'application' => $application,
            'can_evaluate' => $application->isEvaluable(),
        ]);
    }

    public function viewPhoto(Application $application): StreamedResponse|HttpResponse
    {
        $application->loadMissing('user');

        $path = $application->user?->foto_path;
        abort_if($path === null || trim((string) $path) === '', 404);

        $path = (string) $path;

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return redirect()->away($path);
        }

        foreach (['public', (string) config('filesystems.default', 'local')] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->response($path);
            }
        }

        abort(404);
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
            Mail::to($application->user)->queue(new DocumentoRecusado($applicationDocument));
        }

        $pointsApplied = $this->documentValidationScoringService->applyDocumentDecision(
            $application,
            $applicationDocument->fresh(),
            $applicationDocument->status,
            (int) auth()->id(),
        );

        $message = 'Documento validado.';
        if ($pointsApplied !== null) {
            $message = $applicationDocument->status === 'aprovado'
                ? sprintf('Documento aprovado. %.2f pontos somados à pontuação do candidato.', $pointsApplied)
                : 'Documento recusado. A pontuação deste título foi zerada.';
        }

        return back()->with('success', $message);
    }
}
