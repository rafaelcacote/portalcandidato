<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use App\Modules\Shared\Enums\ApplicationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('process show exposes draft application id when inscription is rascunho', function (): void {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    $application = Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => ApplicationStatus::Rascunho->value,
    ]);

    $this->actingAs($candidate)
        ->get(route('candidate.processes.show', $process))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Processes/Show')
            ->where('alreadyApplied', false)
            ->where('draftApplicationId', $application->id)
        );
});

test('process show marks already applied when inscription is finalizada', function (): void {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => ApplicationStatus::Inscrita->value,
        'numero_protocolo' => 'PS-2026-000001',
        'finalizada_em' => now(),
    ]);

    $this->actingAs($candidate)
        ->get(route('candidate.processes.show', $process))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Processes/Show')
            ->where('alreadyApplied', true)
            ->where('draftApplicationId', null)
        );
});
