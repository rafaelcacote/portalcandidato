<?php

use App\Models\Modules\Admin\Models\ProcessStage;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('admin can add and remove process stages', function (): void {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Etapas',
        'descricao' => 'Teste de etapas',
        'regras' => 'Regras',
        'status' => 'rascunho',
        'tipo_programa' => 'mestrado',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.processes.stages.store', $process), [
            'nome' => 'Inscrições',
            'ordem' => 1,
            'inicio_em' => '2026-06-01T08:00',
            'fim_em' => '2026-06-30T23:59',
        ])
        ->assertRedirect();

    $stage = ProcessStage::query()->firstOrFail();

    expect($stage->selection_process_id)->toBe($process->id)
        ->and($stage->nome)->toBe('Inscrições')
        ->and($stage->ordem)->toBe(1);

    $this->actingAs($admin)
        ->delete(route('admin.processes.stages.destroy', [
            'selectionProcess' => $process->id,
            'processStage' => $stage->id,
        ]))
        ->assertRedirect();

    expect(ProcessStage::query()->count())->toBe(0);
});

test('admin cannot remove stage from another process', function (): void {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $processA = SelectionProcess::query()->create([
        'titulo' => 'Processo A',
        'descricao' => 'A',
        'regras' => 'A',
        'status' => 'rascunho',
        'tipo_programa' => 'mestrado',
    ]);

    $processB = SelectionProcess::query()->create([
        'titulo' => 'Processo B',
        'descricao' => 'B',
        'regras' => 'B',
        'status' => 'rascunho',
        'tipo_programa' => 'mestrado',
    ]);

    $stage = $processB->stages()->create([
        'nome' => 'Provas',
        'ordem' => 1,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.processes.stages.destroy', [
            'selectionProcess' => $processA->id,
            'processStage' => $stage->id,
        ]))
        ->assertNotFound();

    expect(ProcessStage::query()->count())->toBe(1);
});
