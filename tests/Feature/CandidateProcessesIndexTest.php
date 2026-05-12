<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use App\Modules\Shared\Enums\ApplicationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('guest is redirected from candidate processes index', function (): void {
    $this->get(route('candidate.processes.index'))
        ->assertRedirect(route('login'));
});

test('non-candidate cannot access candidate processes index', function (): void {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('candidate.processes.index'))
        ->assertForbidden();
});

test('candidate processes index lists active processes with edital download url when edital is linked', function (): void {
    Storage::fake('local');
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Com Edital',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    $path = 'process-editais/'.$process->id.'/edital.pdf';
    Storage::disk('local')->put($path, '%PDF-1.4 fake');
    $process->update(['edital_pdf_path' => $path]);

    $expectedUrl = route('selection-processes.edital.show', $process);

    $this->actingAs($candidate)
        ->get(route('candidate.processes.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Processes/Index')
            ->has('processes.data', 1)
            ->where('processes.data.0.edital_download_url', $expectedUrl)
        );
});

test('candidate processes index has null edital url when process has no edital file', function (): void {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    SelectionProcess::query()->create([
        'titulo' => 'PS Sem Edital',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    $this->actingAs($candidate)
        ->get(route('candidate.processes.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Processes/Index')
            ->has('processes.data', 1)
            ->where('processes.data.0.edital_download_url', null)
        );
});

test('candidate processes index exposes filters prop', function (): void {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $this->actingAs($candidate)
        ->get(route('candidate.processes.index', ['search' => 'Mestrado', 'tipo_programa' => 'mestrado']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Processes/Index')
            ->where('filters.search', 'Mestrado')
            ->where('filters.tipo_programa', 'mestrado')
        );
});

test('candidate processes index filters by tipo_programa', function (): void {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    SelectionProcess::query()->create([
        'titulo' => 'Mestrado em Eng',
        'descricao' => 'D',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    SelectionProcess::query()->create([
        'titulo' => 'Doutorado em Física',
        'descricao' => 'D',
        'status' => 'ativo',
        'tipo_programa' => 'doutorado',
    ]);

    $this->actingAs($candidate)
        ->get(route('candidate.processes.index', ['tipo_programa' => 'doutorado']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Processes/Index')
            ->has('processes.data', 1)
            ->where('processes.data.0.titulo', 'Doutorado em Física')
        );
});

test('candidate processes index filters by search term', function (): void {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    SelectionProcess::query()->create([
        'titulo' => 'Programa de Biologia',
        'descricao' => 'D',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    SelectionProcess::query()->create([
        'titulo' => 'Programa de Física',
        'descricao' => 'D',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    $this->actingAs($candidate)
        ->get(route('candidate.processes.index', ['search' => 'Biologia']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Processes/Index')
            ->has('processes.data', 1)
            ->where('processes.data.0.titulo', 'Programa de Biologia')
        );
});

test('candidate processes index maps draft application ids for rascunho applications', function (): void {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Rascunho',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    $application = Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => ApplicationStatus::Rascunho->value,
    ]);

    $this->actingAs($candidate)
        ->get(route('candidate.processes.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Processes/Index')
            ->where('draftApplicationIdsByProcessId.'.$process->id, $application->id)
        );
});
