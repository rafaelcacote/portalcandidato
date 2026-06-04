<?php

namespace App\Modules\Candidate\Services;

use App\Models\Modules\Candidate\Models\Application;
use App\Modules\Shared\Enums\ApplicationStatus;

class EnrollmentFinalizeReminderService
{
    public const SESSION_SHOWN_KEY = 'enrollment_finalize_reminder_shown';

    /** Toast visibility for finalize reminders (default app toasts use 5s). */
    public const TOAST_LIFE_MS = 10_000;

    public function hasDraftEnrollment(int $userId): bool
    {
        return Application::query()
            ->where('user_id', $userId)
            ->where('status', ApplicationStatus::Rascunho->value)
            ->exists();
    }

    public function entryMessage(): string
    {
        return 'Você tem inscrição em andamento. Acesse a etapa «Revisar Inscrição» e clique em «Finalizar inscrição» para concluir.';
    }

    public function stepSavedMessage(int $step): string
    {
        return "Etapa {$step} salva. Lembre-se de finalizar sua inscrição na etapa «Revisar Inscrição» para enviá-la.";
    }
}
