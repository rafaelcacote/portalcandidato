<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('candidate can start and submit application flow', function () {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital Teste',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);

    $this->actingAs($candidate)
        ->post(route('candidate.applications.start', $process))
        ->assertRedirect();

    $application = Application::query()->firstOrFail();

    $this->actingAs($candidate)
        ->post(route('candidate.applications.step.store', ['application' => $application, 'step' => 1]), [
            'payload' => [
                'concorre_vagas_pcd' => false,
            ],
        ])
        ->assertRedirect();

    $this->actingAs($candidate)
        ->post(route('candidate.applications.step.store', ['application' => $application, 'step' => 3]), [
            'payload' => validApplicationStep3Payload(),
        ])
        ->assertRedirect();

    $this->actingAs($candidate)
        ->post(route('candidate.applications.submit', $application))
        ->assertRedirect();

    $application->refresh();
    expect($application->status)->toBe('inscrita')
        ->and($application->numero_protocolo)->not->toBeNull()
        ->and($application->dados_inscricao['step_3']['linha_pesquisa'])->toBe('linha_1')
        ->and($application->dados_inscricao['step_3']['orientador'])->toBe('Dr. Aldalice Aguiar de Souza');
});
