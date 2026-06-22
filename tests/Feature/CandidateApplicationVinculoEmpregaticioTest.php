<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('candidate can save employment bond step as false', function () {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital Vínculo',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
    ]);

    $this->actingAs($candidate)
        ->post(route('candidate.applications.step.store', ['application' => $application, 'step' => 2]), [
            'payload' => ['concorre_vagas_sem_vinculo' => false],
        ])
        ->assertRedirect();

    $application->refresh();

    expect($application->dados_inscricao['step_2'])->toBe(['concorre_vagas_sem_vinculo' => false]);
});

test('candidate can save employment bond step as true', function () {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital Vínculo',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
    ]);

    $this->actingAs($candidate)
        ->post(route('candidate.applications.step.store', ['application' => $application, 'step' => 2]), [
            'payload' => ['concorre_vagas_sem_vinculo' => true],
        ])
        ->assertRedirect();

    $application->refresh();

    expect($application->dados_inscricao['step_2'])->toBe(['concorre_vagas_sem_vinculo' => true]);
});

test('step 2 requires concorre_vagas_sem_vinculo field', function () {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital Vínculo',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
    ]);

    $this->actingAs($candidate)
        ->post(route('candidate.applications.step.store', ['application' => $application, 'step' => 2]), [
            'payload' => [],
        ])
        ->assertSessionHasErrors('payload.concorre_vagas_sem_vinculo');
});

test('candidate cannot submit without employment bond step', function () {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital Vínculo',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
        'dados_inscricao' => [
            'step_1' => ['concorre_vagas_pcd' => false],
            'step_3' => validApplicationStep3Payload(),
        ],
    ]);

    $this->actingAs($candidate)
        ->post(route('candidate.applications.submit', $application))
        ->assertSessionHasErrors('submit');

    $application->refresh();
    expect($application->status)->toBe('rascunho');
});

test('candidate can submit with employment bond step answered', function () {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital Vínculo',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);
    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
        'dados_inscricao' => [
            'step_1' => ['concorre_vagas_pcd' => false],
            'step_2' => ['concorre_vagas_sem_vinculo' => true],
            'step_3' => validApplicationStep3Payload(),
        ],
    ]);

    $this->actingAs($candidate)
        ->post(route('candidate.applications.submit', $application))
        ->assertRedirect();

    $application->refresh();
    expect($application->status)->toBe('inscrita');
});
