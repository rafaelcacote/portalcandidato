<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('evaluator process show paginates candidates with twenty per page', function (): void {
    Role::findOrCreate('candidato', 'web');
    Role::findOrCreate('avaliador', 'web');

    $evaluator = User::factory()->create(['email_verified_at' => now()]);
    $evaluator->assignRole('avaliador');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Paginação',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    $evaluator->evaluatorAssignments()->create([
        'selection_process_id' => $process->id,
        'pode_avaliar' => true,
        'pode_visualizar_resultados' => false,
        'pode_baixar_documentos' => true,
    ]);

    for ($i = 0; $i < 26; $i++) {
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('candidato');

        Application::query()->create(evaluableApplicationAttributes([
            'user_id' => $candidate->id,
            'selection_process_id' => $process->id,
            'status' => 'inscrita',
        ]));
    }

    $this->actingAs($evaluator)
        ->get(route('evaluator.processes.show', $process))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Evaluator/Processes/Show')
            ->has('candidates.data', 20)
            ->where('candidates.total', 26)
            ->where('candidates.current_page', 1)
            ->where('candidates.last_page', 2)
            ->where('candidates.per_page', 20));

    $this->actingAs($evaluator)
        ->get(route('evaluator.processes.show', [$process, 'page' => 2]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('candidates.data', 6)
            ->where('candidates.total', 26)
            ->where('candidates.current_page', 2)
            ->where('candidates.last_page', 2));
});

test('evaluator process index paginates assigned processes with twelve per page', function (): void {
    Role::findOrCreate('avaliador', 'web');

    $evaluator = User::factory()->create(['email_verified_at' => now()]);
    $evaluator->assignRole('avaliador');

    for ($i = 0; $i < 13; $i++) {
        $process = SelectionProcess::query()->create([
            'titulo' => "PS {$i}",
            'descricao' => 'Descrição',
            'status' => 'ativo',
        ]);

        $evaluator->evaluatorAssignments()->create([
            'selection_process_id' => $process->id,
            'pode_avaliar' => true,
            'pode_visualizar_resultados' => false,
            'pode_baixar_documentos' => true,
        ]);
    }

    $this->actingAs($evaluator)
        ->get(route('evaluator.processes.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Evaluator/Processes/Index')
            ->has('processes.data', 12)
            ->where('processes.total', 13)
            ->where('processes.current_page', 1)
            ->where('processes.last_page', 2)
            ->where('processes.per_page', 12));
});

test('evaluator process show filters candidates by inscrita status', function (): void {
    Role::findOrCreate('candidato', 'web');
    Role::findOrCreate('avaliador', 'web');

    $evaluator = User::factory()->create(['email_verified_at' => now()]);
    $evaluator->assignRole('avaliador');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Filtro',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    $evaluator->evaluatorAssignments()->create([
        'selection_process_id' => $process->id,
        'pode_avaliar' => true,
        'pode_visualizar_resultados' => false,
        'pode_baixar_documentos' => true,
    ]);

    $inscritaCandidate = User::factory()->create(['email_verified_at' => now()]);
    $inscritaCandidate->assignRole('candidato');

    $draftCandidate = User::factory()->create(['email_verified_at' => now()]);
    $draftCandidate->assignRole('candidato');

    Application::query()->create(evaluableApplicationAttributes([
        'user_id' => $inscritaCandidate->id,
        'selection_process_id' => $process->id,
        'status' => 'inscrita',
    ]));

    Application::query()->create([
        'user_id' => $draftCandidate->id,
        'selection_process_id' => $process->id,
        'status' => 'rascunho',
    ]);

    $this->actingAs($evaluator)
        ->get(route('evaluator.processes.show', [$process, 'status' => 'inscrita']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('candidates.data', 1)
            ->where('candidates.data.0.status', 'inscrita')
            ->where('filters.status', 'inscrita'));
});

test('evaluator pending candidate counts include inscrita applications', function (): void {
    Role::findOrCreate('candidato', 'web');
    Role::findOrCreate('avaliador', 'web');

    $evaluator = User::factory()->create(['email_verified_at' => now()]);
    $evaluator->assignRole('avaliador');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Pendentes',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    $evaluator->evaluatorAssignments()->create([
        'selection_process_id' => $process->id,
        'pode_avaliar' => true,
        'pode_visualizar_resultados' => false,
        'pode_baixar_documentos' => true,
    ]);

    $inscritaCandidate = User::factory()->create(['email_verified_at' => now()]);
    $inscritaCandidate->assignRole('candidato');

    Application::query()->create(evaluableApplicationAttributes([
        'user_id' => $inscritaCandidate->id,
        'selection_process_id' => $process->id,
        'status' => 'inscrita',
    ]));

    $this->actingAs($evaluator)
        ->get(route('evaluator.processes.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('processes.data.0.pending_candidates', 1));

    $this->actingAs($evaluator)
        ->get(route('evaluator.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.pending_analysis', 1));
});
