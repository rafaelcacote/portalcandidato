<?php

use App\Mail\InscricaoPrazoEncerrando;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use App\Modules\Candidate\Services\EnrollmentDeadlineReminderService;
use App\Modules\Shared\Enums\ApplicationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function verifiedCandidate(): User
{
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    return $candidate;
}

test('deadline reminder is sent for draft application when enrollment ends in two days', function (): void {
    Mail::fake();

    $now = now(config('app.timezone'))->startOfDay()->setHour(9);
    $this->travelTo($now);

    $candidate = verifiedCandidate();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Mestrado PROENS',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'inscricao_inicio_em' => $now->copy()->subDays(5),
        'inscricao_fim_em' => $now->copy()->addDays(2)->setHour(23)->setMinute(59),
    ]);

    $application = Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => ApplicationStatus::Rascunho->value,
    ]);

    $sent = app(EnrollmentDeadlineReminderService::class)->sendPendingReminders($now);

    expect($sent)->toBe(1);

    Mail::assertQueued(InscricaoPrazoEncerrando::class, function (InscricaoPrazoEncerrando $mail) use ($candidate, $application): bool {
        return $mail->hasTo($candidate->email)
            && $mail->application->is($application)
            && $mail->envelope()->subject === 'Faltam 2 dias para encerrar as inscrições — finalize sua inscrição';
    });

    $application->refresh();

    expect($application->enrollment_deadline_reminder_sent_at)->not->toBeNull();
});

test('deadline reminder is not sent twice for the same application', function (): void {
    Mail::fake();

    $now = now(config('app.timezone'))->startOfDay()->setHour(9);
    $this->travelTo($now);

    $candidate = verifiedCandidate();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Doutorado',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'inscricao_inicio_em' => $now->copy()->subDays(3),
        'inscricao_fim_em' => $now->copy()->addDays(2)->endOfDay(),
    ]);

    Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => ApplicationStatus::Rascunho->value,
        'enrollment_deadline_reminder_sent_at' => now(),
    ]);

    $sent = app(EnrollmentDeadlineReminderService::class)->sendPendingReminders($now);

    expect($sent)->toBe(0);
    Mail::assertNothingQueued();
});

test('deadline reminder is not sent when enrollment ends in three days', function (): void {
    Mail::fake();

    $now = now(config('app.timezone'))->startOfDay()->setHour(9);
    $this->travelTo($now);

    $candidate = verifiedCandidate();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo futuro',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'inscricao_inicio_em' => $now->copy()->subDay(),
        'inscricao_fim_em' => $now->copy()->addDays(3)->endOfDay(),
    ]);

    Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => ApplicationStatus::Rascunho->value,
    ]);

    $sent = app(EnrollmentDeadlineReminderService::class)->sendPendingReminders($now);

    expect($sent)->toBe(0);
    Mail::assertNothingQueued();
});

test('deadline reminder is not sent for finalized applications', function (): void {
    Mail::fake();

    $now = now(config('app.timezone'))->startOfDay()->setHour(9);
    $this->travelTo($now);

    $candidate = verifiedCandidate();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'inscricao_inicio_em' => $now->copy()->subDays(10),
        'inscricao_fim_em' => $now->copy()->addDays(2)->endOfDay(),
    ]);

    Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => ApplicationStatus::Inscrita->value,
        'numero_protocolo' => 'PS-2026-000099',
        'finalizada_em' => now(),
    ]);

    $sent = app(EnrollmentDeadlineReminderService::class)->sendPendingReminders($now);

    expect($sent)->toBe(0);
    Mail::assertNothingQueued();
});

test('send enrollment deadline reminders command dispatches emails', function (): void {
    Mail::fake();

    $now = now(config('app.timezone'))->startOfDay()->setHour(9);
    $this->travelTo($now);

    $candidate = verifiedCandidate();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'inscricao_inicio_em' => $now->copy()->subDays(2),
        'inscricao_fim_em' => $now->copy()->addDays(2)->endOfDay(),
    ]);

    Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => ApplicationStatus::Rascunho->value,
    ]);

    Artisan::call('candidate:send-enrollment-deadline-reminders');

    Mail::assertQueued(InscricaoPrazoEncerrando::class);
});
