<?php

namespace App\Modules\Admin\Services;

use App\Mail\RecursoRespondido;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\ApplicationAppeal;
use App\Modules\Shared\Enums\ApplicationAppealStatus;
use Illuminate\Support\Facades\Mail;

class ApplicationAppealAdminService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listForProcess(SelectionProcess $selectionProcess): array
    {
        return ApplicationAppeal::query()
            ->whereHas('application', fn ($q) => $q->where('selection_process_id', $selectionProcess->id))
            ->with([
                'application.user',
                'processStage',
                'respondidoPor',
            ])
            ->latest()
            ->get()
            ->map(fn (ApplicationAppeal $appeal): array => [
                'id' => $appeal->id,
                'texto' => $appeal->texto,
                'status' => $appeal->status,
                'status_label' => ApplicationAppealStatus::tryFrom($appeal->status)?->label() ?? $appeal->status,
                'resposta' => $appeal->resposta,
                'created_at' => $appeal->created_at?->toIso8601String(),
                'respondido_em' => $appeal->respondido_em?->toIso8601String(),
                'application' => [
                    'id' => $appeal->application->id,
                    'numero_protocolo' => $appeal->application->numero_protocolo,
                    'user_name' => $appeal->application->user?->name,
                    'user_email' => $appeal->application->user?->email,
                ],
                'stage' => $appeal->processStage !== null
                    ? [
                        'id' => $appeal->processStage->id,
                        'nome' => $appeal->processStage->nome,
                        'ordem' => $appeal->processStage->ordem,
                    ]
                    : null,
                'respondido_por' => $appeal->respondidoPor !== null
                    ? ['name' => $appeal->respondidoPor->name]
                    : null,
            ])
            ->all();
    }

    public function respond(ApplicationAppeal $appeal, string $status, ?string $resposta): ApplicationAppeal
    {
        $appeal->update([
            'status' => $status,
            'resposta' => $resposta,
            'respondido_por' => auth()->id(),
            'respondido_em' => now(),
        ]);

        $appeal->refresh()->loadMissing([
            'application.user',
            'application.selectionProcess',
            'processStage',
        ]);

        $candidate = $appeal->application?->user;

        if ($candidate?->email) {
            Mail::to($candidate)->queue(new RecursoRespondido($appeal));
        }

        return $appeal;
    }

    public function pendingCountForProcess(SelectionProcess $selectionProcess): int
    {
        return ApplicationAppeal::query()
            ->whereHas('application', fn ($q) => $q->where('selection_process_id', $selectionProcess->id))
            ->whereIn('status', [
                ApplicationAppealStatus::Enviado->value,
                ApplicationAppealStatus::EmAnalise->value,
            ])
            ->count();
    }
}
