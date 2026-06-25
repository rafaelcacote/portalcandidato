<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use App\Modules\Candidate\Support\ResearchLineCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('mestrado process exposes only its configured advisors on linha 2', function () {
    $process = SelectionProcess::query()->create([
        'titulo' => 'EDITALNº054/2026 - Mestrado Profissional em Enfermagem em Saúde Pública',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);

    config()->set('research_lines.by_process_id', array_merge(
        config('research_lines.by_process_id'),
        [
            $process->id => config('research_lines.by_process_id')[2],
        ],
    ));

    $options = ResearchLineCatalog::forFrontend($process->id);

    expect($options['advisors']['linha_2'])
        ->toContain('Dra. Kassia Janara Veras Lima')
        ->not->toContain('Dra. Jacqueline de Almeida Gonçalves Sachett');
});

test('doutorado process exposes only its configured advisors on linha 2', function () {
    $process = SelectionProcess::query()->create([
        'titulo' => 'EDITAL Nº053/2026 - Doutorado Profissional em Enfermagem em Saúde Pública',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);

    config()->set('research_lines.by_process_id', array_merge(
        config('research_lines.by_process_id'),
        [
            $process->id => config('research_lines.by_process_id')[1],
        ],
    ));

    $options = ResearchLineCatalog::forFrontend($process->id);

    expect($options['advisors']['linha_2'])
        ->toContain('Dra. Jacqueline de Almeida Gonçalves Sachett')
        ->not->toContain('Dra. Kassia Janara Veras Lima');
});

test('candidate cannot save doutorado-only advisor on mestrado process', function () {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $process = SelectionProcess::query()->create([
        'titulo' => 'EDITALNº054/2026 - Mestrado Profissional em Enfermagem em Saúde Pública',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);

    config()->set('research_lines.by_process_id', array_merge(
        config('research_lines.by_process_id'),
        [
            $process->id => config('research_lines.by_process_id')[2],
        ],
    ));

    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
    ]);

    $this->actingAs($candidate)
        ->post(route('candidate.applications.step.store', ['application' => $application, 'step' => 3]), [
            'payload' => [
                'linha_pesquisa' => 'linha_2',
                'orientador' => 'Dra. Jacqueline de Almeida Gonçalves Sachett',
            ],
        ])
        ->assertSessionHasErrors('payload.orientador');
});

test('candidate can save mestrado-only advisor on mestrado process', function () {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $process = SelectionProcess::query()->create([
        'titulo' => 'EDITALNº054/2026 - Mestrado Profissional em Enfermagem em Saúde Pública',
        'descricao' => 'Descricao',
        'status' => 'ativo',
    ]);

    config()->set('research_lines.by_process_id', array_merge(
        config('research_lines.by_process_id'),
        [
            $process->id => config('research_lines.by_process_id')[2],
        ],
    ));

    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
    ]);

    $this->actingAs($candidate)
        ->post(route('candidate.applications.step.store', ['application' => $application, 'step' => 3]), [
            'payload' => [
                'linha_pesquisa' => 'linha_2',
                'orientador' => 'Dra. Kassia Janara Veras Lima',
            ],
        ])
        ->assertRedirect(route('candidate.applications.show', $application).'?step=4');
});
