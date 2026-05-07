<?php

use App\Models\User;
use App\Models\Modules\Admin\Models\ProcessApplicationField;
use App\Models\Modules\Admin\Models\ProcessCriteria;
use App\Models\Modules\Admin\Models\ProcessRequiredDocument;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Admin\Models\TipoDocumento;
use App\Models\Modules\Admin\Models\TipoTitulo;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ->assertRedirect();

    $process = \App\Models\Modules\Admin\Models\SelectionProcess::query()->firstOrFail();

    $this->actingAs($admin)
        ->put(route('admin.processes.update', $process), [
            'titulo' => 'PS 2026 Atualizado',
            'descricao' => 'Processo seletivo de teste',
            'regras' => 'Regras gerais',
            'status' => 'ativo',
            'inscricao_inicio_em' => now()->toDateTimeString(),
            'inscricao_fim_em' => now()->addDays(10)->toDateTimeString(),
        ])
        ->assertRedirect();

    expect($process->fresh()->status)->toBe('ativo');
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
        'descricao' => 'Diploma',
        'status' => true,
    ]);
    $tipoTitulo = TipoTitulo::query()->create([
        'descricao' => 'Graduacao',
        'status' => true,
        'calculo' => 'soma',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.processes.required-documents.store', $process), [
            'tipo_documento_id' => $tipoDocumento->id,
            'tipo_titulo_id' => $tipoTitulo->id,
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

test('admin can manage process type registries', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.processes.types.documentos.store'), [
            'descricao' => 'Histórico escolar',
            'status' => true,
        ])
        ->assertRedirect();

    $tipoDocumento = TipoDocumento::query()->firstOrFail();

    $this->actingAs($admin)
        ->put(route('admin.processes.types.documentos.update', $tipoDocumento), [
            'descricao' => 'Documento oficial',
            'status' => false,
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->post(route('admin.processes.types.titulos.store'), [
            'descricao' => 'Especialização',
            'status' => true,
            'calculo' => 'valor',
        ])
        ->assertRedirect();

    $tipoTitulo = TipoTitulo::query()->firstOrFail();

    $this->actingAs($admin)
        ->put(route('admin.processes.types.titulos.update', $tipoTitulo), [
            'descricao' => 'Mestrado',
            'status' => true,
            'calculo' => 'data',
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->delete(route('admin.processes.types.documentos.destroy', $tipoDocumento))
        ->assertRedirect();

    $this->actingAs($admin)
        ->delete(route('admin.processes.types.titulos.destroy', $tipoTitulo))
        ->assertRedirect();

    expect(TipoDocumento::query()->count())->toBe(0);
    expect(TipoTitulo::query()->count())->toBe(0);
});
