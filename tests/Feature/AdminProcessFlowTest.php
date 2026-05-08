<?php

use App\Models\Modules\Admin\Models\ProcessApplicationField;
use App\Models\Modules\Admin\Models\ProcessCriteria;
use App\Models\Modules\Admin\Models\ProcessRequiredDocument;
use App\Models\Modules\Admin\Models\ProcessRequiredTitulo;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Admin\Models\TipoDocumento;
use App\Models\Modules\Admin\Models\TipoTitulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Support\SessionKey;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('admin can create and update a selection process', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.processes.store'), [
            'titulo' => 'PS 2026',
            'descricao' => 'Processo seletivo de teste',
            'regras' => 'Regras gerais',
            'status' => 'rascunho',
            'inscricao_inicio_em' => now()->toDateTimeString(),
            'inscricao_fim_em' => now()->addDays(10)->toDateTimeString(),
        ])
        ->assertRedirect()
        ->assertSessionHas(SessionKey::FLASH_DATA, function (mixed $value): bool {
            /** @var array<string, mixed> $payload */
            $payload = is_array($value) ? $value : [];

            return ($payload['toast']['type'] ?? null) === 'success'
                && ($payload['toast']['message'] ?? null) === 'Processo seletivo criado com sucesso.';
        });

    $process = SelectionProcess::query()->firstOrFail();

    $this->actingAs($admin)
        ->put(route('admin.processes.update', $process), [
            'titulo' => 'PS 2026 Atualizado',
            'descricao' => 'Processo seletivo de teste',
            'regras' => 'Regras gerais',
            'status' => 'ativo',
            'inscricao_inicio_em' => now()->toDateTimeString(),
            'inscricao_fim_em' => now()->addDays(10)->toDateTimeString(),
        ])
        ->assertRedirect()
        ->assertSessionHas(SessionKey::FLASH_DATA, function (mixed $value): bool {
            /** @var array<string, mixed> $payload */
            $payload = is_array($value) ? $value : [];

            return ($payload['toast']['type'] ?? null) === 'success'
                && ($payload['toast']['message'] ?? null) === 'Processo seletivo atualizado com sucesso.';
        });

    expect($process->fresh()->status)->toBe('ativo');
});

test('selection process store rejects end date before start date', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.processes.store'), [
            'titulo' => 'PS 2026',
            'descricao' => 'Processo seletivo de teste',
            'regras' => null,
            'status' => 'rascunho',
            'inscricao_inicio_em' => now()->addDays(5)->toDateTimeString(),
            'inscricao_fim_em' => now()->toDateTimeString(),
        ])
        ->assertSessionHasErrors('inscricao_fim_em');
});

test('selection process store requires titulo and descricao', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.processes.store'), [
            'titulo' => '',
            'descricao' => '',
            'regras' => null,
            'status' => 'rascunho',
        ])
        ->assertSessionHasErrors(['titulo', 'descricao']);
});

test('selection process update rejects end date before start date', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Update Validação',
        'descricao' => 'Descrição',
        'status' => 'rascunho',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.processes.update', $process), [
            'titulo' => $process->titulo,
            'descricao' => $process->descricao,
            'regras' => null,
            'status' => 'rascunho',
            'inscricao_inicio_em' => now()->addDays(10)->toDateTimeString(),
            'inscricao_fim_em' => now()->toDateTimeString(),
        ])
        ->assertSessionHasErrors('inscricao_fim_em');
});

test('admin can configure required documents, criteria and dynamic application fields', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Configuração',
        'descricao' => 'Configuração de teste',
        'regras' => 'Regras de teste',
        'status' => 'rascunho',
    ]);
    $tipoDocumento = TipoDocumento::query()->create([
        'descricao' => 'RG',
        'status' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.processes.required-documents.store', $process), [
            'tipo_documento_id' => $tipoDocumento->id,
            'descricao' => 'RG ou CNH',
            'formatos_aceitos' => 'pdf,jpg,png',
            'tamanho_max_mb' => 15,
            'obrigatorio' => true,
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->post(route('admin.processes.criteria.store', $process), [
            'nome' => 'Análise curricular',
            'peso' => 2.5,
            'pontuacao_max' => 50,
            'ordem' => 1,
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->post(route('admin.processes.application-fields.store', $process), [
            'label' => 'CPF',
            'field_key' => 'cpf',
            'tipo' => 'text',
            'obrigatorio' => true,
            'opcoes' => null,
            'ordem' => 1,
        ])
        ->assertRedirect();

    $requiredDocument = ProcessRequiredDocument::query()->firstOrFail();
    $criteria = ProcessCriteria::query()->firstOrFail();
    $applicationField = ProcessApplicationField::query()->firstOrFail();

    expect($requiredDocument->selection_process_id)->toBe($process->id);
    expect($requiredDocument->tipo_documento_id)->toBe($tipoDocumento->id);
    expect($requiredDocument->nome)->toBe('RG');
    expect($criteria->selection_process_id)->toBe($process->id);
    expect($applicationField->selection_process_id)->toBe($process->id);

    $this->actingAs($admin)
        ->delete(route('admin.processes.required-documents.destroy', [
            'selectionProcess' => $process->id,
            'processRequiredDocument' => $requiredDocument->id,
        ]))
        ->assertRedirect();

    $this->actingAs($admin)
        ->delete(route('admin.processes.criteria.destroy', [
            'selectionProcess' => $process->id,
            'processCriteria' => $criteria->id,
        ]))
        ->assertRedirect();

    $this->actingAs($admin)
        ->delete(route('admin.processes.application-fields.destroy', [
            'selectionProcess' => $process->id,
            'processApplicationField' => $applicationField->id,
        ]))
        ->assertRedirect();

    expect(ProcessRequiredDocument::query()->count())->toBe(0);
    expect(ProcessCriteria::query()->count())->toBe(0);
    expect(ProcessApplicationField::query()->count())->toBe(0);
});

test('admin can attach and remove required titulos with permissions', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Títulos',
        'descricao' => 'Configuração de títulos',
        'status' => 'rascunho',
    ]);
    $tipoTitulo = TipoTitulo::query()->create([
        'descricao' => 'Mestrado',
        'status' => true,
        'calculo' => 'valor',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.processes.required-titulos.store', $process), [
            'tipo_titulo_id' => $tipoTitulo->id,
            'pontuacao_max' => 25.50,
            'qtd_maxima' => 2,
            'obrigatorio' => false,
            'formatos_aceitos' => 'pdf,jpg',
            'tamanho_max_mb' => 20,
            'descricao' => 'Apresentar diploma de Mestrado',
        ])
        ->assertRedirect();

    $requiredTitulo = ProcessRequiredTitulo::query()->firstOrFail();

    expect($requiredTitulo->selection_process_id)->toBe($process->id);
    expect($requiredTitulo->tipo_titulo_id)->toBe($tipoTitulo->id);
    expect((float) $requiredTitulo->pontuacao_max)->toBe(25.50);
    expect($requiredTitulo->qtd_maxima)->toBe(2);
    expect($requiredTitulo->formatos_aceitos)->toBe(['pdf', 'jpg']);
    expect($requiredTitulo->obrigatorio)->toBeFalse();

    $this->actingAs($admin)
        ->post(route('admin.processes.required-titulos.store', $process), [
            'tipo_titulo_id' => $tipoTitulo->id,
            'pontuacao_max' => 10,
            'obrigatorio' => false,
            'tamanho_max_mb' => 10,
        ])
        ->assertSessionHasErrors('tipo_titulo_id');

    $this->actingAs($admin)
        ->delete(route('admin.processes.required-titulos.destroy', [
            'selectionProcess' => $process->id,
            'processRequiredTitulo' => $requiredTitulo->id,
        ]))
        ->assertRedirect();

    expect(ProcessRequiredTitulo::query()->count())->toBe(0);
});

test('cannot attach the same required document twice to the same process', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Único Doc',
        'descricao' => 'Descrição',
        'status' => 'rascunho',
    ]);
    $tipoDocumento = TipoDocumento::query()->create([
        'descricao' => 'CPF',
        'status' => true,
    ]);

    $process->requiredDocuments()->create([
        'tipo_documento_id' => $tipoDocumento->id,
        'nome' => $tipoDocumento->descricao,
        'tamanho_max_mb' => 10,
        'obrigatorio' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.processes.required-documents.store', $process), [
            'tipo_documento_id' => $tipoDocumento->id,
            'tamanho_max_mb' => 10,
            'obrigatorio' => true,
        ])
        ->assertSessionHasErrors('tipo_documento_id');
});

test('admin can manage process type registries', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.processes.types.documentos.store'), [
            'descricao' => 'Histórico escolar',
            'status' => true,
        ])
        ->assertRedirect(route('admin.support-tables.document-types.index'))
        ->assertSessionHas(SessionKey::FLASH_DATA, function (mixed $value): bool {
            /** @var array<string, mixed> $payload */
            $payload = is_array($value) ? $value : [];

            return ($payload['toast']['type'] ?? null) === 'success'
                && ($payload['toast']['message'] ?? null) === 'Tipo de documento criado com sucesso.';
        });

    $tipoDocumento = TipoDocumento::query()->firstOrFail();

    $this->actingAs($admin)
        ->put(route('admin.processes.types.documentos.update', $tipoDocumento), [
            'descricao' => 'Documento oficial',
            'status' => false,
        ])
        ->assertRedirect(route('admin.support-tables.document-types.index'));

    $this->actingAs($admin)
        ->post(route('admin.processes.types.titulos.store'), [
            'descricao' => 'Especialização',
            'status' => true,
            'calculo' => 'valor',
        ])
        ->assertRedirect(route('admin.support-tables.title-types.index'));

    $tipoTitulo = TipoTitulo::query()->firstOrFail();

    $this->actingAs($admin)
        ->put(route('admin.processes.types.titulos.update', $tipoTitulo), [
            'descricao' => 'Mestrado',
            'status' => true,
            'calculo' => 'data',
        ])
        ->assertRedirect(route('admin.support-tables.title-types.index'));

    $this->actingAs($admin)
        ->delete(route('admin.processes.types.documentos.destroy', $tipoDocumento))
        ->assertRedirect();

    $this->actingAs($admin)
        ->delete(route('admin.processes.types.titulos.destroy', $tipoTitulo))
        ->assertRedirect();

    expect(TipoDocumento::query()->count())->toBe(0);
    expect(TipoTitulo::query()->count())->toBe(0);
});

test('document type store requires descricao and title type store requires descricao and calculo', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.processes.types.documentos.store'), [
            'descricao' => '',
            'status' => true,
        ])
        ->assertSessionHasErrors('descricao');

    $this->actingAs($admin)
        ->post(route('admin.processes.types.titulos.store'), [
            'descricao' => 'Título válido',
            'status' => true,
            'calculo' => 'invalido',
        ])
        ->assertSessionHasErrors('calculo');
});

test('admin can open document and title type create and edit screens', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.support-tables.document-types.create'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('admin.support-tables.title-types.create'))
        ->assertOk();

    $tipoDocumento = TipoDocumento::query()->create([
        'descricao' => 'Certidão',
        'status' => true,
    ]);
    $tipoTitulo = TipoTitulo::query()->create([
        'descricao' => 'Curso livre',
        'status' => true,
        'calculo' => 'valor',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.support-tables.document-types.edit', $tipoDocumento))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('admin.support-tables.title-types.edit', $tipoTitulo))
        ->assertOk();
});
