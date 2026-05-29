<?php

namespace App\Modules\Candidate\Services;

use App\Models\Modules\Admin\Models\ProcessStage;
use App\Models\Modules\Candidate\Models\Application;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ApplicationPdfService
{
    public function downloadInscriptionReceipt(Application $application): Response
    {
        $application->loadMissing(['user', 'selectionProcess']);

        return Pdf::loadView('pdfs.comprovante-inscricao', $this->sharedViewData($application))
            ->setPaper('a4', 'portrait')
            ->download($this->filename($application, 'comprovante-inscricao'));
    }

    public function downloadStageDeclaration(Application $application, ProcessStage $processStage): Response
    {
        abort_unless($processStage->selection_process_id === $application->selection_process_id, 404);

        $application->loadMissing(['user', 'selectionProcess.stages']);

        $declarationKind = app(ApplicationProfessionalDocumentService::class)
            ->stageDeclarationKind($application, $processStage);

        return Pdf::loadView('pdfs.declaracao-etapa', array_merge(
            $this->sharedViewData($application),
            [
                'stage' => $processStage,
                'declarationKind' => $declarationKind,
                'declarationTitle' => $declarationKind === 'aprovacao'
                    ? 'Declaração de Aprovação'
                    : 'Declaração de Participação',
            ],
        ))
            ->setPaper('a4', 'portrait')
            ->download($this->filename($application, 'declaracao-'.str($processStage->nome)->slug()));
    }

    public function inlineInscriptionReceipt(Application $application): SymfonyResponse
    {
        $application->loadMissing(['user', 'selectionProcess']);

        return Pdf::loadView('pdfs.comprovante-inscricao', $this->sharedViewData($application))
            ->setPaper('a4', 'portrait')
            ->stream($this->filename($application, 'comprovante-inscricao'));
    }

    public function inlineStageDeclaration(Application $application, ProcessStage $processStage): SymfonyResponse
    {
        abort_unless($processStage->selection_process_id === $application->selection_process_id, 404);

        $application->loadMissing(['user', 'selectionProcess.stages']);

        $declarationKind = app(ApplicationProfessionalDocumentService::class)
            ->stageDeclarationKind($application, $processStage);

        return Pdf::loadView('pdfs.declaracao-etapa', array_merge(
            $this->sharedViewData($application),
            [
                'stage' => $processStage,
                'declarationKind' => $declarationKind,
                'declarationTitle' => $declarationKind === 'aprovacao'
                    ? 'Declaração de Aprovação'
                    : 'Declaração de Participação',
            ],
        ))
            ->setPaper('a4', 'portrait')
            ->stream($this->filename($application, 'declaracao-'.str($processStage->nome)->slug()));
    }

    /**
     * @return array<string, mixed>
     */
    private function sharedViewData(Application $application): array
    {
        return [
            'application' => $application,
            'candidate' => $application->user,
            'process' => $application->selectionProcess,
            'institution' => config('lgpd.data_controller'),
            'generatedAt' => now()->timezone(config('app.timezone'))->format('d/m/Y H:i'),
        ];
    }

    private function filename(Application $application, string $suffix): string
    {
        $protocol = $application->numero_protocolo ?? 'sem-protocolo';

        return str($suffix.'-'.$protocol)->slug().'.pdf';
    }
}
