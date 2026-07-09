<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use App\Modules\Shared\Enums\ApplicationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createAdminUserForTemporaryBackfill(): User
{
    Role::findOrCreate('admin', 'web');

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    return $admin;
}

function createEnrolledApplicationWithoutResearchLine(
    SelectionProcess $process,
    string $name,
    string $protocol,
): Application {
    Role::findOrCreate('candidato', 'web');

    $candidate = User::factory()->create([
        'name' => $name,
        'cpf' => '52998224725',
        'email_verified_at' => now(),
    ]);
    $candidate->assignRole('candidato');

    return Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => ApplicationStatus::Inscrita->value,
        'numero_protocolo' => $protocol,
        'finalizada_em' => now(),
        'dados_inscricao' => [
            'step_1' => ['concorre_vagas_pcd' => false],
            'step_2' => ['concorre_vagas_sem_vinculo' => false],
        ],
    ]);
}

test('guest is redirected from temporary missing research lines index', function (): void {
    $this->get(route('admin.temporary.missing-research-lines.index'))
        ->assertRedirect(route('login'));
});

test('non-admin cannot access temporary missing research lines', function (): void {
    Role::findOrCreate('candidato', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $this->actingAs($candidate)
        ->get(route('admin.temporary.missing-research-lines.index'))
        ->assertForbidden();
});

test('admin sees only processes with missing research lines', function (): void {
    $admin = createAdminUserForTemporaryBackfill();

    $processWithMissing = SelectionProcess::query()->create([
        'titulo' => 'Processo com pendência',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    $processComplete = SelectionProcess::query()->create([
        'titulo' => 'Processo completo',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    createEnrolledApplicationWithoutResearchLine($processWithMissing, 'Ana Costa', '2026-0001');

    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()->create([
        'name' => 'Bruno Lima',
        'cpf' => '39053344705',
        'email_verified_at' => now(),
    ]);
    $candidate->assignRole('candidato');

    Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $processComplete->id,
        'status' => ApplicationStatus::Inscrita->value,
        'numero_protocolo' => '2026-0002',
        'finalizada_em' => now(),
        'dados_inscricao' => [
            'step_3' => validApplicationStep3Payload(),
        ],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.temporary.missing-research-lines.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Temporary/MissingResearchLines/Index')
            ->has('processes', 1)
            ->where('processes.0.titulo', 'Processo com pendência')
            ->where('processes.0.missing_research_lines_count', 1)
        );
});

test('admin can list candidates missing research line for a process', function (): void {
    $admin = createAdminUserForTemporaryBackfill();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Doutorado 2026',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    createEnrolledApplicationWithoutResearchLine($process, 'Carlos Silva', '2026-0100');

    $this->actingAs($admin)
        ->get(route('admin.temporary.missing-research-lines.processes.show', $process))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Temporary/MissingResearchLines/Show')
            ->where('selectionProcess.titulo', 'Processo Doutorado 2026')
            ->has('applications', 1)
            ->where('applications.0.nome_completo', 'Carlos Silva')
            ->has('researchLineOptions.lines', 2)
        );
});

test('admin can update missing research line for an application', function (): void {
    $admin = createAdminUserForTemporaryBackfill();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Atualização 2026',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    $application = createEnrolledApplicationWithoutResearchLine($process, 'Diana Souza', '2026-0200');

    $this->actingAs($admin)
        ->put(route('admin.temporary.missing-research-lines.applications.update', $application), [
            'linha_pesquisa' => 'linha_1',
            'orientador' => 'Dra. Aldalice Aguiar de Souza',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $application->refresh();

    expect($application->dados_inscricao['step_3']['linha_pesquisa'])->toBe('linha_1')
        ->and($application->dados_inscricao['step_3']['orientador'])->toBe('Dra. Aldalice Aguiar de Souza');
});

test('admin cannot overwrite an application that already has research line', function (): void {
    $admin = createAdminUserForTemporaryBackfill();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Protegido 2026',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()->create([
        'name' => 'Eduardo Lima',
        'cpf' => '15350946056',
        'email_verified_at' => now(),
    ]);
    $candidate->assignRole('candidato');

    $application = Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => ApplicationStatus::Inscrita->value,
        'numero_protocolo' => '2026-0300',
        'finalizada_em' => now(),
        'dados_inscricao' => [
            'step_3' => validApplicationStep3Payload(),
        ],
    ]);

    $this->actingAs($admin)
        ->from(route('admin.temporary.missing-research-lines.processes.show', $process))
        ->put(route('admin.temporary.missing-research-lines.applications.update', $application), [
            'linha_pesquisa' => 'linha_2',
            'orientador' => 'Dra. Amélia Nunes Sicsú',
        ])
        ->assertRedirect(route('admin.temporary.missing-research-lines.processes.show', $process));

    $application->refresh();

    expect($application->dados_inscricao['step_3']['linha_pesquisa'])->toBe('linha_1');
});

test('update validates advisor for selected research line', function (): void {
    $admin = createAdminUserForTemporaryBackfill();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Validação 2026',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    $application = createEnrolledApplicationWithoutResearchLine($process, 'Fernanda Alves', '2026-0400');

    $this->actingAs($admin)
        ->put(route('admin.temporary.missing-research-lines.applications.update', $application), [
            'linha_pesquisa' => 'linha_1',
            'orientador' => 'Dra. Amélia Nunes Sicsú',
        ])
        ->assertSessionHasErrors('orientador');
});
