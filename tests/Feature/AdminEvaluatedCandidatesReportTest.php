<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Evaluator\Models\ApplicationEvaluation;
use App\Models\User;
use App\Modules\Admin\Services\EvaluatedCandidatesReportService;
use App\Modules\Shared\Enums\ApplicationStatus;
use App\Modules\Shared\Enums\EvaluationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createEvaluatedReportAdmin(): User
{
    Role::findOrCreate('admin', 'web');

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    return $admin;
}

/**
 * @param  array<string, mixed>  $dadosInscricao
 */
function createEvaluatedCandidate(
    SelectionProcess $process,
    string $name,
    string $cpf,
    string $protocol,
    float $nota,
    ?array $dadosInscricao = null,
    string $evaluationStatus = 'concluida',
): Application {
    Role::findOrCreate('candidato', 'web');
    Role::findOrCreate('avaliador', 'web');

    $candidate = User::factory()->create([
        'name' => $name,
        'cpf' => $cpf,
        'email_verified_at' => now(),
    ]);
    $candidate->assignRole('candidato');

    $evaluator = User::factory()->create(['email_verified_at' => now()]);
    $evaluator->assignRole('avaliador');

    $application = Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => ApplicationStatus::EmAnalise->value,
        'numero_protocolo' => $protocol,
        'finalizada_em' => now(),
        'dados_inscricao' => $dadosInscricao ?? [
            'step_3' => validApplicationStep3Payload(),
        ],
    ]);

    ApplicationEvaluation::query()->create([
        'application_id' => $application->id,
        'evaluator_id' => $evaluator->id,
        'status' => $evaluationStatus,
        'resultado' => 'classificado',
        'pontuacao_total' => $nota,
        'concluida_em' => $evaluationStatus === EvaluationStatus::Concluida->value ? now() : null,
    ]);

    return $application;
}

test('guest is redirected from evaluated candidates report', function (): void {
    $this->get(route('admin.reports.evaluated'))
        ->assertRedirect(route('login'));
});

test('non-admin cannot access evaluated candidates report', function (): void {
    Role::findOrCreate('candidato', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $this->actingAs($candidate)
        ->get(route('admin.reports.evaluated'))
        ->assertForbidden();
});

test('admin sees evaluated candidates with codigo nome cpf and nota', function (): void {
    $admin = createEvaluatedReportAdmin();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Avaliados 2026',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    createEvaluatedCandidate(
        $process,
        'Maria Avaliada',
        '52998224725',
        '2026-9001',
        17.5,
    );

    $this->actingAs($admin)
        ->get(route('admin.reports.evaluated'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reports/Evaluated')
            ->has('candidates.data', 1)
            ->where('candidates.data.0.numero_protocolo', '2026-9001')
            ->where('candidates.data.0.nome_completo', 'Maria Avaliada')
            ->where('candidates.data.0.cpf_mascarado', '529.***.***-25')
            ->where('candidates.data.0.nota', 17.5)
            ->missing('candidates.data.0.cpf')
            ->has('filterOptions.processes')
            ->has('filterOptions.researchLines')
        );
});

test('evaluated candidates report excludes candidates without concluded evaluation', function (): void {
    $admin = createEvaluatedReportAdmin();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Parcial 2026',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    createEvaluatedCandidate(
        $process,
        'Ainda Em Análise',
        '39053344705',
        '2026-9002',
        10.0,
        null,
        EvaluationStatus::EmAnalise->value,
    );

    createEvaluatedCandidate(
        $process,
        'Já Concluída',
        '11144477735',
        '2026-9003',
        12.0,
    );

    $this->actingAs($admin)
        ->get(route('admin.reports.evaluated'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('candidates.data', 1)
            ->where('candidates.data.0.numero_protocolo', '2026-9003')
            ->where('candidates.data.0.nome_completo', 'Já Concluída')
        );
});

test('admin can filter evaluated candidates by selection process and linha de pesquisa', function (): void {
    $admin = createEvaluatedReportAdmin();

    $processA = SelectionProcess::query()->create([
        'titulo' => 'Processo A Filtro',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    $processB = SelectionProcess::query()->create([
        'titulo' => 'Processo B Filtro',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'tipo_programa' => 'doutorado',
    ]);

    createEvaluatedCandidate(
        $processA,
        'Ana Processo A Linha 1',
        '52998224725',
        '2026-9101',
        15.0,
        [
            'step_3' => [
                'linha_pesquisa' => 'linha_1',
                'orientador' => 'Dra. Aldalice Aguiar de Souza',
            ],
        ],
    );

    createEvaluatedCandidate(
        $processA,
        'Bruno Processo A Linha 2',
        '39053344705',
        '2026-9102',
        18.0,
        [
            'step_3' => [
                'linha_pesquisa' => 'linha_2',
                'orientador' => 'Dra. Amélia Nunes Sicsú',
            ],
        ],
    );

    createEvaluatedCandidate(
        $processB,
        'Carla Processo B Linha 1',
        '11144477735',
        '2026-9103',
        20.0,
        [
            'step_3' => [
                'linha_pesquisa' => 'linha_1',
                'orientador' => 'Dra. Aldalice Aguiar de Souza',
            ],
        ],
    );

    $this->actingAs($admin)
        ->get(route('admin.reports.evaluated', [
            'selection_process_id' => $processA->id,
            'linha_pesquisa' => 'linha_1',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reports/Evaluated')
            ->has('candidates.data', 1)
            ->where('candidates.data.0.numero_protocolo', '2026-9101')
            ->where('candidates.data.0.nome_completo', 'Ana Processo A Linha 1')
            ->where('candidates.data.0.nota', 15)
            ->where('filters.selection_process_id', $processA->id)
            ->where('filters.linha_pesquisa', 'linha_1')
        );
});

test('evaluated candidates report is ordered by highest nota first', function (): void {
    $admin = createEvaluatedReportAdmin();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Ordenação Avaliados',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    createEvaluatedCandidate(
        $process,
        'Nota Mais Baixa',
        '39053344705',
        '2026-9301',
        10.0,
    );

    createEvaluatedCandidate(
        $process,
        'Nota Mais Alta',
        '11144477735',
        '2026-9302',
        22.5,
    );

    createEvaluatedCandidate(
        $process,
        'Nota Intermediária',
        '15350946056',
        '2026-9303',
        15.0,
    );

    $this->actingAs($admin)
        ->get(route('admin.reports.evaluated'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('candidates.data.0.nome_completo', 'Nota Mais Alta')
            ->where('candidates.data.0.nota', 22.5)
            ->where('candidates.data.1.nome_completo', 'Nota Intermediária')
            ->where('candidates.data.1.nota', 15)
            ->where('candidates.data.2.nome_completo', 'Nota Mais Baixa')
            ->where('candidates.data.2.nota', 10)
        );
});

test('admin can generate evaluated candidates print pdf with filters', function (): void {
    $admin = createEvaluatedReportAdmin();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo PDF Avaliados',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    createEvaluatedCandidate(
        $process,
        'Paula Avaliada',
        '15350946056',
        '2026-9201',
        16.5,
        [
            'step_3' => [
                'linha_pesquisa' => 'linha_1',
                'orientador' => 'Dra. Aldalice Aguiar de Souza',
            ],
        ],
    );

    $this->actingAs($admin)
        ->get(route('admin.reports.evaluated.print', [
            'selection_process_id' => $process->id,
            'linha_pesquisa' => 'linha_1',
        ]))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});

test('non-admin cannot print evaluated candidates report', function (): void {
    Role::findOrCreate('candidato', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $this->actingAs($candidate)
        ->get(route('admin.reports.evaluated.print'))
        ->assertForbidden();
});

test('evaluated candidates pdf filter labels describe active filters', function (): void {
    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Labels Avaliados',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    $labels = app(EvaluatedCandidatesReportService::class)
        ->activeFilterLabels([
            'selection_process_id' => $process->id,
            'linha_pesquisa' => 'linha_1',
        ]);

    expect($labels)->toBe([
        'Processo seletivo: Processo Labels Avaliados',
        'Linha de pesquisa: Linha de Pesquisa 1',
    ]);
});
