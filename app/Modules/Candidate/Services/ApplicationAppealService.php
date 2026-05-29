<?php

namespace App\Modules\Candidate\Services;

use App\Models\Modules\Admin\Models\ProcessStage;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationAppeal;
use App\Modules\Shared\Enums\ApplicationAppealStatus;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ApplicationAppealService
{
    public function isRecursoWindowOpen(ProcessStage $stage): bool
    {
        $start = $stage->recurso_inicio_em ?? $stage->fim_em;
        $end = $stage->recurso_fim_em ?? $stage->fim_em?->copy()->addDays(5);

        if ($start === null || $end === null) {
            return false;
        }

        return now()->between($start, $end);
    }

    /**
     * @return list<array{id: int, nome: string, ordem: int, recurso_aberto: bool, recurso_inicio_em: string|null, recurso_fim_em: string|null}>
     */
    public function listStagesForApplication(Application $application): array
    {
        $application->loadMissing('selectionProcess.stages');

        return ($application->selectionProcess?->stages ?? collect())
            ->sortBy('ordem')
            ->map(fn (ProcessStage $stage): array => [
                'id' => $stage->id,
                'nome' => $stage->nome,
                'ordem' => $stage->ordem,
                'recurso_aberto' => $this->isRecursoWindowOpen($stage),
                'recurso_inicio_em' => ($stage->recurso_inicio_em ?? $stage->fim_em)?->toIso8601String(),
                'recurso_fim_em' => ($stage->recurso_fim_em ?? $stage->fim_em?->copy()->addDays(5))?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    public function store(Application $application, ?int $processStageId, string $texto): ApplicationAppeal
    {
        if (! $application->isFinalizedForDocuments()) {
            throw ValidationException::withMessages([
                'texto' => 'Recursos só podem ser enviados após a finalização da inscrição.',
            ]);
        }

        $stage = null;

        if ($processStageId !== null) {
            $stage = ProcessStage::query()
                ->where('id', $processStageId)
                ->where('selection_process_id', $application->selection_process_id)
                ->firstOrFail();

            if (! $this->isRecursoWindowOpen($stage)) {
                throw ValidationException::withMessages([
                    'process_stage_id' => 'O prazo para envio de recurso nesta etapa não está aberto.',
                ]);
            }

            $alreadySubmitted = ApplicationAppeal::query()
                ->where('application_id', $application->id)
                ->where('process_stage_id', $stage->id)
                ->exists();

            if ($alreadySubmitted) {
                throw ValidationException::withMessages([
                    'process_stage_id' => 'Você já enviou um recurso para esta etapa.',
                ]);
            }
        }

        return ApplicationAppeal::query()->create([
            'application_id' => $application->id,
            'process_stage_id' => $stage?->id,
            'texto' => $texto,
            'status' => 'enviado',
        ]);
    }

    /**
     * @return list<array{id: int, texto: string, status: string, status_label: string, resposta: string|null, respondido_em: string|null, created_at: string, stage: array{id: int, nome: string}|null}>
     */
    public function listAppealsForApplication(Application $application): array
    {
        return ApplicationAppeal::query()
            ->where('application_id', $application->id)
            ->with('processStage')
            ->latest()
            ->get()
            ->map(fn (ApplicationAppeal $appeal): array => [
                'id' => $appeal->id,
                'texto' => $appeal->texto,
                'status' => $appeal->status,
                'status_label' => ApplicationAppealStatus::tryFrom($appeal->status)?->label() ?? $appeal->status,
                'resposta' => $appeal->resposta,
                'respondido_em' => $appeal->respondido_em?->toIso8601String(),
                'created_at' => $appeal->created_at?->toIso8601String() ?? '',
                'stage' => $appeal->processStage !== null
                    ? ['id' => $appeal->processStage->id, 'nome' => $appeal->processStage->nome]
                    : null,
            ])
            ->all();
    }

    public function hasOpenRecursoWindow(Application $application): bool
    {
        $application->loadMissing('selectionProcess.stages');

        return ($application->selectionProcess?->stages ?? new Collection)
            ->contains(fn (ProcessStage $stage): bool => $this->isRecursoWindowOpen($stage));
    }
}
