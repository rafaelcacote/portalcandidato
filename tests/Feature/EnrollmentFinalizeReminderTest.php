<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use App\Modules\Candidate\Services\EnrollmentFinalizeReminderService;
use App\Modules\Shared\Enums\ApplicationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Support\SessionKey;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function candidateWithRole(): User
{
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    return $candidate;
}

test('candidate login flashes finalize reminder when draft enrollment exists', function (): void {
    $candidate = candidateWithRole();
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => ApplicationStatus::Rascunho->value,
    ]);

    $this->post(route('login.store'), [
        'email' => $candidate->email,
        'password' => 'password',
    ])
        ->assertRedirect(route('candidate.dashboard', absolute: false))
        ->assertSessionHas(SessionKey::FLASH_DATA, function (mixed $value): bool {
            /** @var array<string, mixed> $payload */
            $payload = is_array($value) ? $value : [];

            return ($payload['toast']['type'] ?? null) === 'warning'
                && str_contains((string) ($payload['toast']['message'] ?? ''), 'Revisar Inscrição');
        })
        ->assertSessionHas(EnrollmentFinalizeReminderService::SESSION_SHOWN_KEY, true);
});

test('candidate login does not flash finalize reminder without draft enrollment', function (): void {
    $candidate = candidateWithRole();

    $this->post(route('login.store'), [
        'email' => $candidate->email,
        'password' => 'password',
    ])
        ->assertRedirect(route('candidate.dashboard', absolute: false))
        ->assertSessionMissing(SessionKey::FLASH_DATA);
});

test('candidate dashboard flashes finalize reminder once per session when draft exists', function (): void {
    $candidate = candidateWithRole();
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => ApplicationStatus::Rascunho->value,
    ]);

    $this->actingAs($candidate)
        ->withSession([])
        ->get(route('candidate.dashboard'))
        ->assertSuccessful()
        ->assertSessionHas(EnrollmentFinalizeReminderService::SESSION_SHOWN_KEY, true);

    $this->actingAs($candidate)
        ->withSession([EnrollmentFinalizeReminderService::SESSION_SHOWN_KEY => true])
        ->get(route('candidate.dashboard'))
        ->assertSuccessful()
        ->assertSessionMissing(SessionKey::FLASH_DATA);
});

test('dashboard does not re-flash reminder after login already showed it', function (): void {
    $candidate = candidateWithRole();
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => ApplicationStatus::Rascunho->value,
    ]);

    $this->post(route('login.store'), [
        'email' => $candidate->email,
        'password' => 'password',
    ])->assertSessionHas(EnrollmentFinalizeReminderService::SESSION_SHOWN_KEY, true);

    $this->actingAs($candidate)
        ->withSession([EnrollmentFinalizeReminderService::SESSION_SHOWN_KEY => true])
        ->get(route('candidate.dashboard'))
        ->assertSuccessful()
        ->assertSessionMissing(SessionKey::FLASH_DATA);
});

test('saving application step flashes finalize reminder while enrollment is draft', function (): void {
    $candidate = candidateWithRole();
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    $application = Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => ApplicationStatus::Rascunho->value,
    ]);

    $this->actingAs($candidate)
        ->post(route('candidate.applications.step.store', ['application' => $application, 'step' => 1]), [
            'payload' => [
                'concorre_vagas_pcd' => false,
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas(SessionKey::FLASH_DATA, function (mixed $value): bool {
            /** @var array<string, mixed> $payload */
            $payload = is_array($value) ? $value : [];

            return ($payload['toast']['type'] ?? null) === 'warning'
                && str_contains((string) ($payload['toast']['message'] ?? ''), 'Etapa 1 salva')
                && str_contains((string) ($payload['toast']['message'] ?? ''), 'Revisar Inscrição');
        });
});
