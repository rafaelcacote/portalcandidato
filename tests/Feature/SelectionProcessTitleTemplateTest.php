<?php

use App\Models\Modules\Admin\Models\ProcessTitleGroup;
use App\Models\Modules\Admin\Models\ProcessTitleItem;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('mestrado template creates 6 title groups marked as generated_by_template', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.processes.store'), [
            'titulo' => 'PS Mestrado Títulos',
            'descricao' => 'D',
            'regras' => null,
            'status' => 'rascunho',
            'tipo_programa' => 'mestrado',
        ])
        ->assertRedirect();

    $process = SelectionProcess::query()->where('titulo', 'PS Mestrado Títulos')->firstOrFail();

    $groups = ProcessTitleGroup::query()
        ->where('selection_process_id', $process->id)
        ->where('generated_by_template', true)
        ->orderBy('order')
        ->get();

    expect($groups)->toHaveCount(6)
        ->and($groups->pluck('code')->all())->toBe(['A', 'B', 'C', 'D', 'E', 'F']);
});

test('doutorado template creates 6 title groups marked as generated_by_template', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.processes.store'), [
            'titulo' => 'PS Doutorado Títulos',
            'descricao' => 'D',
            'regras' => null,
            'status' => 'rascunho',
            'tipo_programa' => 'doutorado',
        ])
        ->assertRedirect();

    $process = SelectionProcess::query()->where('titulo', 'PS Doutorado Títulos')->firstOrFail();

    $groups = ProcessTitleGroup::query()
        ->where('selection_process_id', $process->id)
        ->where('generated_by_template', true)
        ->orderBy('order')
        ->get();

    expect($groups)->toHaveCount(6)
        ->and($groups->pluck('code')->all())->toBe(['A', 'B', 'C', 'D', 'E', 'F']);
});

test('mestrado group A has items with diploma de especialista', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.processes.store'), [
            'titulo' => 'PS Mestrado A',
            'descricao' => 'D',
            'regras' => null,
            'status' => 'rascunho',
            'tipo_programa' => 'mestrado',
        ])
        ->assertRedirect();

    $process = SelectionProcess::query()->where('titulo', 'PS Mestrado A')->firstOrFail();

    $groupA = ProcessTitleGroup::query()
        ->where('selection_process_id', $process->id)
        ->where('code', 'A')
        ->firstOrFail();

    $item = ProcessTitleItem::query()
        ->where('process_title_group_id', $groupA->id)
        ->where('code', 'A1')
        ->firstOrFail();

    expect($item->title)->toContain('Especialista em Saúde Pública')
        ->and((float) $item->score_per_unit)->toBe(1.00);
});

test('doutorado group A has items with diploma de mestre', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.processes.store'), [
            'titulo' => 'PS Doutorado A',
            'descricao' => 'D',
            'regras' => null,
            'status' => 'rascunho',
            'tipo_programa' => 'doutorado',
        ])
        ->assertRedirect();

    $process = SelectionProcess::query()->where('titulo', 'PS Doutorado A')->firstOrFail();

    $groupA = ProcessTitleGroup::query()
        ->where('selection_process_id', $process->id)
        ->where('code', 'A')
        ->firstOrFail();

    $item = ProcessTitleItem::query()
        ->where('process_title_group_id', $groupA->id)
        ->where('code', 'A1')
        ->firstOrFail();

    expect($item->title)->toContain('Diploma de Mestre')
        ->and((float) $item->score_per_unit)->toBe(1.00);
});

test('mestrado and doutorado have different max_score for group B', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.processes.store'), [
            'titulo' => 'PS Mestrado B',
            'descricao' => 'D',
            'status' => 'rascunho',
            'tipo_programa' => 'mestrado',
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->post(route('admin.processes.store'), [
            'titulo' => 'PS Doutorado B',
            'descricao' => 'D',
            'status' => 'rascunho',
            'tipo_programa' => 'doutorado',
        ])
        ->assertRedirect();

    $mestrado = SelectionProcess::query()->where('titulo', 'PS Mestrado B')->firstOrFail();
    $doutorado = SelectionProcess::query()->where('titulo', 'PS Doutorado B')->firstOrFail();

    $groupBMestrado = ProcessTitleGroup::query()
        ->where('selection_process_id', $mestrado->id)
        ->where('code', 'B')
        ->firstOrFail();

    $groupBDoutorado = ProcessTitleGroup::query()
        ->where('selection_process_id', $doutorado->id)
        ->where('code', 'B')
        ->firstOrFail();

    expect((float) $groupBMestrado->max_score)->toBe(4.00)
        ->and((float) $groupBDoutorado->max_score)->toBe(3.50);
});

test('changing tipo_programa resyncs template title groups', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.processes.store'), [
            'titulo' => 'PS Troca Títulos',
            'descricao' => 'D',
            'status' => 'rascunho',
            'tipo_programa' => 'mestrado',
        ])
        ->assertRedirect();

    $process = SelectionProcess::query()->where('titulo', 'PS Troca Títulos')->firstOrFail();

    $groupABefore = ProcessTitleGroup::query()
        ->where('selection_process_id', $process->id)
        ->where('code', 'A')
        ->firstOrFail();

    expect(
        ProcessTitleItem::query()
            ->where('process_title_group_id', $groupABefore->id)
            ->where('code', 'A1')
            ->value('title'),
    )->toContain('Especialista em Saúde Pública');

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

    $groupAAfter = ProcessTitleGroup::query()
        ->where('selection_process_id', $process->id)
        ->where('code', 'A')
        ->firstOrFail();

    expect(
        ProcessTitleItem::query()
            ->where('process_title_group_id', $groupAAfter->id)
            ->where('code', 'A1')
            ->value('title'),
    )->toContain('Diploma de Mestre');
});

test('each title group has correct number of items', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.processes.store'), [
            'titulo' => 'PS Items Count',
            'descricao' => 'D',
            'status' => 'rascunho',
            'tipo_programa' => 'mestrado',
        ])
        ->assertRedirect();

    $process = SelectionProcess::query()->where('titulo', 'PS Items Count')->firstOrFail();

    $groups = ProcessTitleGroup::query()
        ->where('selection_process_id', $process->id)
        ->where('generated_by_template', true)
        ->withCount('items')
        ->get()
        ->keyBy('code');

    expect($groups['A']->items_count)->toBe(4)
        ->and($groups['B']->items_count)->toBe(7)
        ->and($groups['C']->items_count)->toBe(7)
        ->and($groups['D']->items_count)->toBe(6)
        ->and($groups['E']->items_count)->toBe(8)
        ->and($groups['F']->items_count)->toBe(4);
});

test('template group C has C1.1 and C1.2 with distinct scores', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.processes.store'), [
            'titulo' => 'PS C1 Split',
            'descricao' => 'D',
            'status' => 'rascunho',
            'tipo_programa' => 'mestrado',
        ])
        ->assertRedirect();

    $process = SelectionProcess::query()->where('titulo', 'PS C1 Split')->firstOrFail();

    $groupC = ProcessTitleGroup::query()
        ->where('selection_process_id', $process->id)
        ->where('code', 'C')
        ->firstOrFail();

    $c11 = ProcessTitleItem::query()
        ->where('process_title_group_id', $groupC->id)
        ->where('code', 'C1.1')
        ->firstOrFail();

    $c12 = ProcessTitleItem::query()
        ->where('process_title_group_id', $groupC->id)
        ->where('code', 'C1.2')
        ->firstOrFail();

    expect((float) $c11->score_per_unit)->toBe(0.50)
        ->and((float) $c12->score_per_unit)->toBe(0.30)
        ->and(
            ProcessTitleItem::query()
                ->where('process_title_group_id', $groupC->id)
                ->where('code', 'C1')
                ->exists(),
        )->toBeFalse();
});

test('template group D has D4.1 D4.2 and D4.3 with distinct scores', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.processes.store'), [
            'titulo' => 'PS D4 Split',
            'descricao' => 'D',
            'status' => 'rascunho',
            'tipo_programa' => 'mestrado',
        ])
        ->assertRedirect();

    $process = SelectionProcess::query()->where('titulo', 'PS D4 Split')->firstOrFail();

    $groupD = ProcessTitleGroup::query()
        ->where('selection_process_id', $process->id)
        ->where('code', 'D')
        ->firstOrFail();

    $scores = ProcessTitleItem::query()
        ->where('process_title_group_id', $groupD->id)
        ->whereIn('code', ['D4.1', 'D4.2', 'D4.3'])
        ->orderBy('order')
        ->pluck('score_per_unit', 'code')
        ->map(fn ($score) => (float) $score);

    expect($scores->all())->toBe([
        'D4.1' => 0.01,
        'D4.2' => 0.03,
        'D4.3' => 0.05,
    ])->and(
        ProcessTitleItem::query()
            ->where('process_title_group_id', $groupD->id)
            ->where('code', 'D4')
            ->exists(),
    )->toBeFalse();
});
