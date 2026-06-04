<?php

namespace App\Modules\Candidate\Services;

use App\Mail\InscricaoPrazoEncerrando;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Modules\Shared\Enums\ApplicationStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class EnrollmentDeadlineReminderService
{
    public const DAYS_BEFORE_DEADLINE = 2;

    /**
     * @return Collection<int, SelectionProcess>
     */
    public function processesWithInscricaoEndingInDays(int $days = self::DAYS_BEFORE_DEADLINE, ?CarbonInterface $now = null): Collection
    {
        return SelectionProcess::query()
            ->inscricaoEncerraEmDias($days, $now)
            ->get();
    }

    /**
     * @param  Collection<int, SelectionProcess>|array<int, SelectionProcess>  $processes
     * @return Collection<int, Application>
     */
    public function draftApplicationsNeedingReminder(Collection|array $processes): Collection
    {
        $processIds = collect($processes)->pluck('id');

        if ($processIds->isEmpty()) {
            return collect();
        }

        return Application::query()
            ->where('status', ApplicationStatus::Rascunho->value)
            ->whereNull('enrollment_deadline_reminder_sent_at')
            ->whereIn('selection_process_id', $processIds)
            ->whereHas('user', function (Builder $query): void {
                $query->whereNotNull('email_verified_at');
            })
            ->with(['user', 'selectionProcess'])
            ->get();
    }

    public function sendPendingReminders(?CarbonInterface $now = null): int
    {
        $processes = $this->processesWithInscricaoEndingInDays(self::DAYS_BEFORE_DEADLINE, $now);
        $applications = $this->draftApplicationsNeedingReminder($processes);

        $sent = 0;

        foreach ($applications as $application) {
            $user = $application->user;

            if ($user === null) {
                continue;
            }

            Mail::to($user)->queue(new InscricaoPrazoEncerrando($application));

            $updated = Application::query()
                ->whereKey($application->id)
                ->whereNull('enrollment_deadline_reminder_sent_at')
                ->update(['enrollment_deadline_reminder_sent_at' => now()]);

            if ($updated === 1) {
                $sent++;
            }
        }

        return $sent;
    }
}
