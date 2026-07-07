<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function closedProcessCandidate(): array
{
    Role::findOrCreate('candidato', 'web');

    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Encerrado',
        'descricao' => 'Descrição',
        'status' => 'encerrado',
    ]);

    $application = Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => 'rascunho',
    ]);

    return [$candidate, $process, $application];
}

test('candidate cannot start application on closed process', function () {
    [$candidate, $process] = closedProcessCandidate();

    $this->actingAs($candidate)
        ->post(route('candidate.applications.start', $process))
        ->assertRedirect(route('candidate.processes.index'));

    expect(Application::query()->count())->toBe(1);
});

test('candidate can view draft application on closed process in read-only mode', function () {
    [$candidate, , $application] = closedProcessCandidate();

    $this->actingAs($candidate)
        ->get(route('candidate.applications.show', $application))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Candidate/Applications/Show')
            ->where('inscricaoAberta', false)
        );
});

test('candidate cannot save steps on draft application when process is closed', function () {
    [$candidate, , $application] = closedProcessCandidate();

    $this->actingAs($candidate)
        ->post(route('candidate.applications.step.store', ['application' => $application, 'step' => 1]), [
            'payload' => [
                'concorre_vagas_pcd' => false,
            ],
        ])
        ->assertForbidden();
});

test('candidate cannot submit draft application when process is closed', function () {
    [$candidate, , $application] = closedProcessCandidate();

    $application->update([
        'dados_inscricao' => [
            'step_1' => ['concorre_vagas_pcd' => false],
            'step_2' => ['concorre_vagas_sem_vinculo' => false],
            'step_3' => validApplicationStep3Payload(),
        ],
    ]);

    $this->actingAs($candidate)
        ->post(route('candidate.applications.submit', $application))
        ->assertForbidden();

    $application->refresh();
    expect($application->status)->toBe('rascunho')
        ->and($application->finalizada_em)->toBeNull();
});

test('candidate cannot start application when enrollment deadline has passed', function () {
    Role::findOrCreate('candidato', 'web');

    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo com prazo encerrado',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'inscricao_fim_em' => now()->subDay(),
    ]);

    $application = Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => 'rascunho',
    ]);

    $this->actingAs($candidate)
        ->post(route('candidate.applications.step.store', ['application' => $application, 'step' => 1]), [
            'payload' => [
                'concorre_vagas_pcd' => false,
            ],
        ])
        ->assertForbidden();
});
