<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use App\Modules\Shared\Enums\ApplicationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createAdminUser(): User
{
    Role::findOrCreate('admin', 'web');

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    return $admin;
}

function createEnrolledCandidateApplication(
    SelectionProcess $process,
    string $name,
    string $cpf,
    string $protocol,
    ?array $dadosInscricao = null,
): Application {
    Role::findOrCreate('candidato', 'web');

    $candidate = User::factory()->create([
        'name' => $name,
        'cpf' => $cpf,
        'email_verified_at' => now(),
    ]);
    $candidate->assignRole('candidato');

    return Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => ApplicationStatus::Inscrita->value,
        'numero_protocolo' => $protocol,
        'finalizada_em' => now(),
        'dados_inscricao' => $dadosInscricao,
    ]);
}

test('guest is redirected from admin reports index', function (): void {
    $this->get(route('admin.reports.index'))
        ->assertRedirect(route('login'));
});

test('non-admin cannot access admin reports', function (): void {
    Role::findOrCreate('candidato', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $this->actingAs($candidate)
        ->get(route('admin.reports.index'))
        ->assertForbidden();
});

test('admin sees reports process listing', function (): void {
    $admin = createAdminUser();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Mestrado 2026',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    createEnrolledCandidateApplication($process, 'Maria Silva', '52998224725', '2026-0001');

    $this->actingAs($admin)
        ->get(route('admin.reports.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reports/Index')
            ->has('processes', 1)
            ->where('processes.0.titulo', 'Processo Mestrado 2026')
            ->where('processes.0.enrolled_candidates_count', 1)
        );
});

test('admin sees enrolled candidates with masked cpf', function (): void {
    $admin = createAdminUser();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Doutorado 2026',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    createEnrolledCandidateApplication(
        $process,
        'João Souza',
        '52998224725',
        '2026-0042',
        ['step_3' => validApplicationStep3Payload()],
    );

    $this->actingAs($admin)
        ->get(route('admin.reports.processes.show', $process))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reports/Show')
            ->where('selectionProcess.titulo', 'Processo Doutorado 2026')
            ->has('candidates.data', 1)
            ->where('candidates.data.0.numero_protocolo', '2026-0042')
            ->where('candidates.data.0.nome_completo', 'João Souza')
            ->where('candidates.data.0.linha_pesquisa_label', 'Linha de Pesquisa 1')
            ->where('candidates.data.0.cpf_mascarado', '529.***.***-25')
            ->missing('candidates.data.0.cpf')
        );
});

test('enrolled candidates report is ordered alphabetically by name', function (): void {
    $admin = createAdminUser();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Ordenação 2026',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    createEnrolledCandidateApplication($process, 'Zélia Andrade', '39053344705', '2026-0003');
    createEnrolledCandidateApplication($process, 'Ana Costa', '11144477735', '2026-0001');
    createEnrolledCandidateApplication($process, 'Bruno Lima', '15350946056', '2026-0002');

    $this->actingAs($admin)
        ->get(route('admin.reports.processes.show', $process))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('candidates.data.0.nome_completo', 'Ana Costa')
            ->where('candidates.data.1.nome_completo', 'Bruno Lima')
            ->where('candidates.data.2.nome_completo', 'Zélia Andrade')
        );
});

test('draft applications are excluded from enrolled candidates report', function (): void {
    $admin = createAdminUser();
    Role::findOrCreate('candidato', 'web');

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Residência 2026',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    $candidate = User::factory()->create([
        'name' => 'Ana Rascunho',
        'cpf' => '11144477735',
        'email_verified_at' => now(),
    ]);
    $candidate->assignRole('candidato');

    Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => ApplicationStatus::Rascunho->value,
    ]);

    createEnrolledCandidateApplication($process, 'Carlos Finalizado', '39053344705', '2026-0009');

    $this->actingAs($admin)
        ->get(route('admin.reports.processes.show', $process))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('candidates.data', 1)
            ->where('candidates.data.0.nome_completo', 'Carlos Finalizado')
        );
});

test('admin can generate enrolled candidates print pdf', function (): void {
    $admin = createAdminUser();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Impressão 2026',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    createEnrolledCandidateApplication($process, 'Paula Lima', '15350946056', '2026-0100');

    $this->actingAs($admin)
        ->get(route('admin.reports.processes.print', $process))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});

test('non-admin cannot print enrolled candidates report', function (): void {
    Role::findOrCreate('candidato', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Protegido',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    $this->actingAs($candidate)
        ->get(route('admin.reports.processes.print', $process))
        ->assertForbidden();
});
