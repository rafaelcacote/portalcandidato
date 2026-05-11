<?php

use App\Models\Modules\Admin\Models\ProcessEvaluatorAssignment;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Support\SessionKey;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function adminUser(): User
{
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('avaliador', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    return $admin;
}

test('admin can register a new evaluator', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post(route('admin.evaluators.store'), [
            'name' => 'Maria Avaliadora',
            'email' => 'maria.avaliadora@example.com',
            'cpf' => '529.982.247-25',
            'telefone' => '(11) 98888-7777',
            'password' => 'senhaSegura123',
            'ativo' => true,
        ])
        ->assertRedirect()
        ->assertSessionHas(SessionKey::FLASH_DATA, function (mixed $value): bool {
            /** @var array<string, mixed> $payload */
            $payload = is_array($value) ? $value : [];

            return ($payload['toast']['type'] ?? null) === 'success'
                && ($payload['toast']['message'] ?? null) === 'Avaliador cadastrado com sucesso.';
        });

    $evaluator = User::query()
        ->where('email', 'maria.avaliadora@example.com')
        ->firstOrFail();

    expect($evaluator->hasRole('avaliador'))->toBeTrue()
        ->and($evaluator->cpf)->toBe('52998224725');
    expect(Hash::check('senhaSegura123', $evaluator->password))->toBeTrue();
});

test('store evaluator validates required fields and unique email', function () {
    $admin = adminUser();
    User::factory()->create(['email' => 'existente@example.com']);

    $this->actingAs($admin)
        ->post(route('admin.evaluators.store'), [
            'name' => '',
            'email' => 'existente@example.com',
            'password' => '',
        ])
        ->assertSessionHasErrors(['name', 'password', 'email', 'cpf']);
});

test('store evaluator rejects invalid cpf', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post(route('admin.evaluators.store'), [
            'name' => 'Nome',
            'email' => 'novo@example.com',
            'cpf' => '123.456.789-00',
            'password' => 'senhaSegura12',
            'ativo' => true,
        ])
        ->assertSessionHasErrors(['cpf']);
});

test('store evaluator rejects duplicate cpf', function () {
    $admin = adminUser();

    User::factory()->create(['cpf' => '52998224725']);

    $this->actingAs($admin)
        ->post(route('admin.evaluators.store'), [
            'name' => 'Outro',
            'email' => 'outro@example.com',
            'cpf' => '52998224725',
            'password' => 'senhaSegura12',
            'ativo' => true,
        ])
        ->assertSessionHasErrors(['cpf']);
});

test('admin can update evaluator data and keep current password when blank', function () {
    $admin = adminUser();

    $evaluator = User::factory()->create([
        'name' => 'João',
        'email' => 'joao@example.com',
        'password' => Hash::make('original-password'),
    ]);
    $evaluator->assignRole('avaliador');

    $this->actingAs($admin)
        ->put(route('admin.evaluators.update', $evaluator), [
            'name' => 'João Silva',
            'email' => 'joao.silva@example.com',
            'cpf' => '52998224725',
            'telefone' => '(11) 91234-5678',
            'password' => '',
            'ativo' => true,
        ])
        ->assertRedirect();

    $evaluator->refresh();

    expect($evaluator->name)->toBe('João Silva');
    expect($evaluator->email)->toBe('joao.silva@example.com');
    expect($evaluator->cpf)->toBe('52998224725');
    expect(Hash::check('original-password', $evaluator->password))->toBeTrue();
});

test('admin cannot manage non evaluator users through evaluator routes', function () {
    $admin = adminUser();

    $regularUser = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.evaluators.edit', $regularUser))
        ->assertNotFound();

    $this->actingAs($admin)
        ->put(route('admin.evaluators.update', $regularUser), [
            'name' => 'Hack',
            'email' => 'hack@example.com',
        ])
        ->assertNotFound();
});

test('admin can attach an evaluator to a process with permissions', function () {
    $admin = adminUser();

    $evaluator = User::factory()->create();
    $evaluator->assignRole('avaliador');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Avaliação',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.evaluators.assignments.store', $evaluator), [
            'selection_process_id' => $process->id,
            'pode_avaliar' => true,
            'pode_visualizar_resultados' => true,
            'pode_baixar_documentos' => false,
        ])
        ->assertRedirect();

    $assignment = ProcessEvaluatorAssignment::query()
        ->where('user_id', $evaluator->id)
        ->where('selection_process_id', $process->id)
        ->firstOrFail();

    expect($assignment->pode_avaliar)->toBeTrue();
    expect($assignment->pode_visualizar_resultados)->toBeTrue();
    expect($assignment->pode_baixar_documentos)->toBeFalse();
});

test('cannot assign the same evaluator to the same process twice', function () {
    $admin = adminUser();

    $evaluator = User::factory()->create();
    $evaluator->assignRole('avaliador');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Único',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    $evaluator->evaluatorAssignments()->create([
        'selection_process_id' => $process->id,
        'pode_avaliar' => true,
        'pode_visualizar_resultados' => false,
        'pode_baixar_documentos' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.evaluators.assignments.store', $evaluator), [
            'selection_process_id' => $process->id,
            'pode_avaliar' => true,
            'pode_visualizar_resultados' => false,
            'pode_baixar_documentos' => true,
        ])
        ->assertSessionHasErrors('selection_process_id');
});

test('admin can update permissions of an existing assignment', function () {
    $admin = adminUser();

    $evaluator = User::factory()->create();
    $evaluator->assignRole('avaliador');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Permissões',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    $assignment = $evaluator->evaluatorAssignments()->create([
        'selection_process_id' => $process->id,
        'pode_avaliar' => true,
        'pode_visualizar_resultados' => false,
        'pode_baixar_documentos' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.evaluators.assignments.update', [
            'evaluator' => $evaluator->id,
            'assignment' => $assignment->id,
        ]), [
            'pode_avaliar' => false,
            'pode_visualizar_resultados' => true,
            'pode_baixar_documentos' => false,
        ])
        ->assertRedirect();

    $assignment->refresh();

    expect($assignment->pode_avaliar)->toBeFalse();
    expect($assignment->pode_visualizar_resultados)->toBeTrue();
    expect($assignment->pode_baixar_documentos)->toBeFalse();
});

test('admin can remove an assignment', function () {
    $admin = adminUser();

    $evaluator = User::factory()->create();
    $evaluator->assignRole('avaliador');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Remover',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    $assignment = $evaluator->evaluatorAssignments()->create([
        'selection_process_id' => $process->id,
        'pode_avaliar' => true,
        'pode_visualizar_resultados' => false,
        'pode_baixar_documentos' => true,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.evaluators.assignments.destroy', [
            'evaluator' => $evaluator->id,
            'assignment' => $assignment->id,
        ]))
        ->assertRedirect();

    expect(ProcessEvaluatorAssignment::query()->count())->toBe(0);
});

test('admin can delete an evaluator', function () {
    $admin = adminUser();

    $evaluator = User::factory()->create();
    $evaluator->assignRole('avaliador');

    $this->actingAs($admin)
        ->delete(route('admin.evaluators.destroy', $evaluator))
        ->assertRedirect(route('admin.evaluators.index'));

    expect(User::query()->where('id', $evaluator->id)->exists())->toBeFalse();
});

test('admin can list evaluators', function () {
    $admin = adminUser();

    $evaluator = User::factory()->create(['name' => 'Avaliador Listado']);
    $evaluator->assignRole('avaliador');

    $this->actingAs($admin)
        ->get(route('admin.evaluators.index'))
        ->assertOk();
});
