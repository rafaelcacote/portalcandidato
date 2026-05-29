<?php

namespace App\Modules\Candidate\Services;

use App\Models\Modules\Admin\Models\ProcessStage;
use App\Models\Modules\Candidate\Models\Application;
use App\Modules\Shared\Enums\ApplicationStatus;
use Illuminate\Support\Collection;

class ApplicationProfessionalDocumentService
{
    public function assertCanAccessDocuments(Application $application): void
    {
        abort_unless($application->isFinalizedForDocuments(), 403, 'Documentos disponíveis apenas após a finalização da inscrição.');
    }

    /**
     * @return list<array{key: string, label: string, description: string, download_url: string, print_url: string, kind: string, stage_id: int|null}>
     */
    public function listForApplication(Application $application): array
    {
        if (! $application->isFinalizedForDocuments()) {
            return [];
        }

        $application->loadMissing(['selectionProcess.stages']);

        $documents = [
            [
                'key' => 'comprovante-inscricao',
                'label' => 'Comprovante de Inscrição',
                'description' => 'Comprova a realização da inscrição com protocolo, para fins profissionais e institucionais.',
                'download_url' => route('candidate.applications.documents.comprovante', $application),
                'print_url' => route('candidate.applications.documents.comprovante', [
                    'application' => $application,
                    'print' => 1,
                ]),
                'kind' => 'comprovante',
                'stage_id' => null,
            ],
        ];

        $stages = $application->selectionProcess?->stages ?? collect();

        foreach ($stages as $stage) {
            if (! $this->stageDeclarationIsAvailable($application, $stage)) {
                continue;
            }

            $kind = $this->stageDeclarationKind($application, $stage);
            $labelPrefix = $kind === 'aprovacao' ? 'Declaração de Aprovação' : 'Declaração de Participação';

            $documents[] = [
                'key' => 'declaracao-etapa-'.$stage->id,
                'label' => $labelPrefix.' — '.$stage->nome,
                'description' => $kind === 'aprovacao'
                    ? 'Declara a aprovação nesta etapa do processo seletivo, para comprovação profissional.'
                    : 'Declara a participação nesta etapa do processo seletivo, para comprovação profissional.',
                'download_url' => route('candidate.applications.documents.declaracao-etapa', [
                    'application' => $application,
                    'processStage' => $stage,
                ]),
                'print_url' => route('candidate.applications.documents.declaracao-etapa', [
                    'application' => $application,
                    'processStage' => $stage,
                    'print' => 1,
                ]),
                'kind' => 'declaracao',
                'stage_id' => $stage->id,
            ];
        }

        return $documents;
    }

    public function stageDeclarationIsAvailable(Application $application, ProcessStage $stage): bool
    {
        if (! $application->isFinalizedForDocuments()) {
            return false;
        }

        $stages = $application->selectionProcess?->stages ?? new Collection;

        if ($stages->isEmpty()) {
            return false;
        }

        $firstStage = $stages->sortBy('ordem')->first();

        if ($firstStage !== null && $firstStage->id === $stage->id) {
            return true;
        }

        if ($this->isFinalStage($application, $stage)) {
            return in_array($application->status, [
                ApplicationStatus::Aprovada->value,
                ApplicationStatus::Reprovada->value,
                ApplicationStatus::EmAnalise->value,
                ApplicationStatus::Inscrita->value,
            ], true);
        }

        return $stage->fim_em !== null && now()->greaterThanOrEqualTo($stage->fim_em);
    }

    public function stageDeclarationKind(Application $application, ProcessStage $stage): string
    {
        if (
            $this->isFinalStage($application, $stage)
            && $application->status === ApplicationStatus::Aprovada->value
        ) {
            return 'aprovacao';
        }

        return 'participacao';
    }

    public function isFinalStage(Application $application, ProcessStage $stage): bool
    {
        $stages = $application->selectionProcess?->stages;

        if ($stages === null || $stages->isEmpty()) {
            return false;
        }

        $maxOrdem = (int) $stages->max('ordem');

        return (int) $stage->ordem === $maxOrdem;
    }
}
