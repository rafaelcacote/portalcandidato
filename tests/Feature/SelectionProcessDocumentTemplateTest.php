<?php

use App\Models\Modules\Admin\Models\ProcessRequiredDocument;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Support\SessionKey;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('admin store requires tipo_programa', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.processes.store'), [
            'titulo' => 'PS',
            'descricao' => 'D',
            'regras' => null,
            'status' => 'rascunho',
        ])
        ->assertSessionHasErrors('tipo_programa');
});

test('doutorado template uses diploma de mestrado document type', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.processes.store'), [
            'titulo' => 'PS Doutorado',
            'descricao' => 'D',
            'regras' => null,
            'status' => 'rascunho',
            'tipo_programa' => 'doutorado',
        ])
        ->assertRedirect();

    $process = SelectionProcess::query()->where('titulo', 'PS Doutorado')->firstOrFail();

    $diploma = ProcessRequiredDocument::query()
        ->where('selection_process_id', $process->id)
        ->whereHas('tipoDocumento', fn ($q) => $q->where('codigo', 'diploma_mestrado_enfermagem'))
        ->first();

    expect($diploma)->not->toBeNull()
        ->and($diploma->gerado_por_template)->toBeTrue();
});

test('changing tipo_programa resyncs template documents when there are no applications', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.processes.store'), [
            'titulo' => 'PS Troca',
            'descricao' => 'D',
            'regras' => null,
            'status' => 'rascunho',
            'tipo_programa' => 'mestrado',
        ])
        ->assertRedirect();

    $process = SelectionProcess::query()->where('titulo', 'PS Troca')->firstOrFail();

    expect(
        ProcessRequiredDocument::query()
            ->where('selection_process_id', $process->id)
            ->whereHas('tipoDocumento', fn ($q) => $q->where('codigo', 'diploma_graduacao_enfermagem'))
            ->exists(),
    )->toBeTrue();

    $this->actingAs($admin)
        ->put(route('admin.processes.update', $process), [
            'titulo' => $process->titulo,
            'descricao' => $process->descricao,
            'regras' => null,
            'status' => 'rascunho',
            'tipo_programa' => 'doutorado',
            'inscricao_inicio_em' => null,
            'inscricao_fim_em' => null,
        ])
        ->assertRedirect();

    $process->refresh();

    expect(
        ProcessRequiredDocument::query()
            ->where('selection_process_id', $process->id)
            ->whereHas('tipoDocumento', fn ($q) => $q->where('codigo', 'diploma_mestrado_enfermagem'))
            ->exists(),
    )->toBeTrue()
        ->and(
            ProcessRequiredDocument::query()
                ->where('selection_process_id', $process->id)
                ->whereHas('tipoDocumento', fn ($q) => $q->where('codigo', 'diploma_graduacao_enfermagem'))
                ->exists(),
        )->toBeFalse();
});

test('cannot change tipo_programa when process already has applications', function () {
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('candidato', 'web');

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Com Inscrição',
        'descricao' => 'D',
        'status' => 'rascunho',
        'tipo_programa' => 'mestrado',
    ]);

    Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => 'rascunho',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.processes.update', $process), [
            'titulo' => $process->titulo,
            'descricao' => $process->descricao,
            'regras' => null,
            'status' => 'rascunho',
            'tipo_programa' => 'doutorado',
            'inscricao_inicio_em' => null,
            'inscricao_fim_em' => null,
        ])
        ->assertSessionHasErrors('tipo_programa');
});

test('candidate with incomplete profile cannot start application', function () {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    $this->actingAs($candidate)
        ->post(route('candidate.applications.start', $process))
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas(SessionKey::FLASH_DATA, function (mixed $value): bool {
            /** @var array<string, mixed> $payload */
            $payload = is_array($value) ? $value : [];

            return ($payload['toast']['type'] ?? null) === 'error';
        });

    expect(Application::query()->count())->toBe(0);
});
