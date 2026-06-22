<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('candidate can save research line and advisor on step 3', function () {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital Acadêmico',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
    ]);

    $payload = validApplicationStep3Payload();

    $this->actingAs($candidate)
        ->post(route('candidate.applications.step.store', ['application' => $application, 'step' => 3]), [
            'payload' => $payload,
        ])
        ->assertRedirect(
            route('candidate.applications.show', $application).'?step=4',
        );

    $application->refresh();

    expect($application->dados_inscricao['step_3'])->toBe($payload);
});

test('candidate cannot submit without research line step', function () {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital Acadêmico',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
        'dados_inscricao' => [
            'step_1' => ['concorre_vagas_pcd' => false],
            'step_2' => ['concorre_vagas_sem_vinculo' => false],
        ],
    ]);

    $this->actingAs($candidate)
        ->post(route('candidate.applications.submit', $application))
        ->assertSessionHasErrors('submit');

    $application->refresh();
    expect($application->status)->toBe('rascunho');
});

test('step 3 requires valid research line and advisor pair', function () {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital Acadêmico',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
    ]);

    $this->actingAs($candidate)
        ->post(route('candidate.applications.step.store', ['application' => $application, 'step' => 3]), [
            'payload' => [
                'linha_pesquisa' => 'linha_1',
                'orientador' => 'Dr. Wagner Ferreira Monteiro',
            ],
        ])
        ->assertSessionHasErrors('payload.orientador');

    $this->actingAs($candidate)
        ->post(route('candidate.applications.step.store', ['application' => $application, 'step' => 3]), [
            'payload' => [
                'linha_pesquisa' => '',
                'orientador' => '',
            ],
        ])
        ->assertSessionHasErrors(['payload.linha_pesquisa', 'payload.orientador']);
});
