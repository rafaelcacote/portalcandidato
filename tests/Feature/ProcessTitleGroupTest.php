<?php

use App\Models\Modules\Admin\Models\ProcessTitleGroup;
use App\Models\Modules\Admin\Models\ProcessTitleItem;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeAdmin(): User
{
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    return $admin;
}

function makeProcess(): SelectionProcess
{
    return SelectionProcess::query()->create([
        'titulo' => 'Mestrado 2026',
        'descricao' => 'Processo de seleção',
        'status' => 'rascunho',
    ]);
}

test('admin can add a title group to a selection process', function (): void {
    $admin = makeAdmin();
    $process = makeProcess();

    $this->actingAs($admin)
        ->post(route('admin.processes.title-groups.store', $process), [
            'code' => 'A',
            'name' => 'Formação Acadêmica/Titulação',
            'description' => 'Serão considerados apenas dois cursos por titulação.',
            'max_score' => 2.0,
            'order' => 0,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('process_title_groups', [
        'selection_process_id' => $process->id,
        'code' => 'A',
        'name' => 'Formação Acadêmica/Titulação',
        'max_score' => 2.0,
    ]);
});

test('admin cannot add a title group with duplicate code to the same process', function (): void {
    $admin = makeAdmin();
    $process = makeProcess();

    ProcessTitleGroup::query()->create([
        'selection_process_id' => $process->id,
        'code' => 'A',
        'name' => 'Formação Acadêmica',
        'max_score' => 2.0,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.processes.title-groups.store', $process), [
            'code' => 'A',
            'name' => 'Outro grupo',
            'max_score' => 1.0,
            'order' => 1,
        ])
        ->assertSessionHasErrors();
});

test('admin can delete a title group', function (): void {
    $admin = makeAdmin();
    $process = makeProcess();

    $group = ProcessTitleGroup::query()->create([
        'selection_process_id' => $process->id,
        'code' => 'A',
        'name' => 'Formação Acadêmica',
        'max_score' => 2.0,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.processes.title-groups.destroy', [$process, $group]))
        ->assertRedirect();

    $this->assertModelMissing($group);
});

test('admin can add a title item to a group', function (): void {
    $admin = makeAdmin();
    $process = makeProcess();

    $group = ProcessTitleGroup::query()->create([
        'selection_process_id' => $process->id,
        'code' => 'A',
        'name' => 'Formação Acadêmica',
        'max_score' => 2.0,
    ]);

    $this->actingAs($admin)
        ->post(
            route('admin.processes.title-groups.items.store', [$process, $group]),
            [
                'code' => 'A.1',
                'title' => 'Certificado de Especialista em Saúde Pública, com carga horária igual ou superior a 360 horas',
                'score_per_unit' => 1.0,
                'score_unit' => 'por título',
                'max_quantity' => 2,
                'period_rule' => null,
                'requires_attachment' => true,
                'accepted_formats' => 'pdf,jpg,png',
                'max_file_size_mb' => 10,
                'order' => 0,
            ],
        )
        ->assertRedirect();

    $this->assertDatabaseHas('process_title_items', [
        'process_title_group_id' => $group->id,
        'code' => 'A.1',
        'score_per_unit' => 1.0,
        'score_unit' => 'por título',
        'max_quantity' => 2,
    ]);
});

test('admin can delete a title item', function (): void {
    $admin = makeAdmin();
    $process = makeProcess();

    $group = ProcessTitleGroup::query()->create([
        'selection_process_id' => $process->id,
        'code' => 'B',
        'name' => 'Atuação Profissional',
        'max_score' => 4.0,
    ]);

    $item = ProcessTitleItem::query()->create([
        'process_title_group_id' => $group->id,
        'code' => 'B.1',
        'title' => 'Atividade de Enfermagem Assistencial ou de Gestão',
        'score_per_unit' => 0.40,
        'score_unit' => 'por ano',
        'max_quantity' => 5,
        'requires_attachment' => true,
        'max_file_size_mb' => 10,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.processes.title-groups.items.destroy', [$process, $group, $item]))
        ->assertRedirect();

    $this->assertModelMissing($item);
});

test('title group and items are cascade deleted with process', function (): void {
    $admin = makeAdmin();
    $process = makeProcess();

    $group = ProcessTitleGroup::query()->create([
        'selection_process_id' => $process->id,
        'code' => 'A',
        'name' => 'Formação Acadêmica',
        'max_score' => 2.0,
    ]);

    ProcessTitleItem::query()->create([
        'process_title_group_id' => $group->id,
        'code' => 'A.1',
        'title' => 'Especialização',
        'score_per_unit' => 1.0,
        'score_unit' => 'por título',
        'requires_attachment' => true,
        'max_file_size_mb' => 10,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.processes.destroy', $process))
        ->assertRedirect();

    $this->assertDatabaseEmpty('process_title_groups');
    $this->assertDatabaseEmpty('process_title_items');
});

test('configure page loads title groups with items', function (): void {
    $admin = makeAdmin();
    $process = makeProcess();

    $group = ProcessTitleGroup::query()->create([
        'selection_process_id' => $process->id,
        'code' => 'A',
        'name' => 'Formação Acadêmica',
        'max_score' => 2.0,
    ]);

    ProcessTitleItem::query()->create([
        'process_title_group_id' => $group->id,
        'code' => 'A.1',
        'title' => 'Certificado de Especialista',
        'score_per_unit' => 1.0,
        'score_unit' => 'por título',
        'max_quantity' => 2,
        'requires_attachment' => true,
        'max_file_size_mb' => 10,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.processes.show', $process))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Processes/Configure')
            ->has('selectionProcess.title_groups', 1)
            ->has('selectionProcess.title_groups.0.items', 1)
        );
});

test('non-admin cannot manage title groups', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $process = makeProcess();

    $this->actingAs($user)
        ->post(route('admin.processes.title-groups.store', $process), [
            'code' => 'A',
            'name' => 'Teste',
            'max_score' => 1.0,
            'order' => 0,
        ])
        ->assertForbidden();
});
