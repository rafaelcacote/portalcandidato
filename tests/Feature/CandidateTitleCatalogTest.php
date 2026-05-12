<?php

use App\Models\Modules\Admin\Models\ProcessTitleGroup;
use App\Models\Modules\Admin\Models\ProcessTitleItem;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeCandidateUser(): User
{
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    return $candidate;
}

test('candidate process page exposes only active title groups and items', function (): void {
    $candidate = makeCandidateUser();
    $process = SelectionProcess::query()->create([
        'titulo' => 'Edital 2026',
        'descricao' => 'Processo',
        'status' => 'ativo',
    ]);

    $activeGroup = ProcessTitleGroup::query()->create([
        'selection_process_id' => $process->id,
        'code' => 'A',
        'name' => 'Formação',
        'max_score' => 5.0,
        'is_active' => true,
    ]);

    ProcessTitleItem::query()->create([
        'process_title_group_id' => $activeGroup->id,
        'code' => 'A.1',
        'title' => 'Especialização',
        'score_per_unit' => 1.0,
        'score_unit' => 'por título',
        'requires_attachment' => true,
        'max_file_size_mb' => 10,
        'is_active' => true,
    ]);

    ProcessTitleItem::query()->create([
        'process_title_group_id' => $activeGroup->id,
        'code' => 'A.2',
        'title' => 'Item inativo',
        'score_per_unit' => 0.5,
        'score_unit' => 'por ano',
        'requires_attachment' => false,
        'max_file_size_mb' => 10,
        'is_active' => false,
    ]);

    ProcessTitleGroup::query()->create([
        'selection_process_id' => $process->id,
        'code' => 'Z',
        'name' => 'Grupo inativo',
        'max_score' => 1.0,
        'is_active' => false,
    ]);

    $this->actingAs($candidate)
        ->get(route('candidate.processes.show', $process))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Candidate/Processes/Show')
            ->has('selectionProcess.title_groups', 1)
            ->where('selectionProcess.title_groups.0.code', 'A')
            ->has('selectionProcess.title_groups.0.items', 1)
            ->where('selectionProcess.title_groups.0.items.0.code', 'A.1')
        );
});

test('candidate application page loads title catalog on selection process', function (): void {
    $candidate = makeCandidateUser();
    $process = SelectionProcess::query()->create([
        'titulo' => 'Mestrado',
        'descricao' => 'X',
        'status' => 'ativo',
    ]);

    $group = ProcessTitleGroup::query()->create([
        'selection_process_id' => $process->id,
        'code' => 'B',
        'name' => 'Atuação',
        'max_score' => 4.0,
        'is_active' => true,
    ]);

    ProcessTitleItem::query()->create([
        'process_title_group_id' => $group->id,
        'code' => 'B.1',
        'title' => 'Experiência assistencial',
        'score_per_unit' => 0.4,
        'score_unit' => 'por ano',
        'max_quantity' => 5,
        'requires_attachment' => true,
        'max_file_size_mb' => 10,
        'is_active' => true,
    ]);

    $application = Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => 'rascunho',
    ]);

    $this->actingAs($candidate)
        ->get(route('candidate.applications.show', $application))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Candidate/Applications/Show')
            ->has('application.selection_process.title_groups', 1)
            ->where('application.selection_process.title_groups.0.code', 'B')
            ->has('application.selection_process.title_groups.0.items', 1)
        );
});
