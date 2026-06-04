<?php

namespace App\Console\Commands;

use App\Modules\Candidate\Services\EnrollmentDeadlineReminderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('candidate:send-enrollment-deadline-reminders')]
#[Description('Envia e-mail aos candidatos com inscrição em rascunho quando faltam 2 dias para encerrar as inscrições')]
class SendEnrollmentDeadlineRemindersCommand extends Command
{
    public function handle(EnrollmentDeadlineReminderService $reminderService): int
    {
        $sent = $reminderService->sendPendingReminders();

        $this->info("Lembretes de prazo enviados: {$sent}.");

        return self::SUCCESS;
    }
}
