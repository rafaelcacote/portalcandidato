<?php

namespace App\Http\Controllers\Modules\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Modules\Admin\Models\ProcessStage;
use App\Models\Modules\Candidate\Models\Application;
use App\Modules\Candidate\Services\ApplicationPdfService;
use App\Modules\Candidate\Services\ApplicationProfessionalDocumentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplicationDocumentExportController extends Controller
{
    public function __construct(
        private readonly ApplicationPdfService $pdfService,
        private readonly ApplicationProfessionalDocumentService $documentService,
    ) {}

    public function comprovante(Application $application, Request $request): Response
    {
        $this->authorizeApplication($application);
        $this->documentService->assertCanAccessDocuments($application);

        if ($request->boolean('print')) {
            return $this->pdfService->inlineInscriptionReceipt($application);
        }

        return $this->pdfService->downloadInscriptionReceipt($application);
    }

    public function declaracaoEtapa(
        Application $application,
        ProcessStage $processStage,
        Request $request,
    ): Response {
        $this->authorizeApplication($application);
        $this->documentService->assertCanAccessDocuments($application);

        abort_unless(
            $this->documentService->stageDeclarationIsAvailable($application, $processStage),
            403,
            'Esta declaração ainda não está disponível para emissão.',
        );

        if ($request->boolean('print')) {
            return $this->pdfService->inlineStageDeclaration($application, $processStage);
        }

        return $this->pdfService->downloadStageDeclaration($application, $processStage);
    }

    private function authorizeApplication(Application $application): void
    {
        abort_if($application->user_id !== auth()->id(), 403);
    }
}
