<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Evaluator\Models\ApplicationEvaluation;
use App\Models\User;
use App\Modules\Admin\Services\CandidateContactsReportService;
use App\Modules\Shared\Enums\ApplicationStatus;
use App\Modules\Shared\Enums\EvaluationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createContactsReportAdmin(): User
{
    Role::findOrCreate('admin', 'web');

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    return $admin;
}

/**
 * @param  array<string, mixed>|null  $dadosInscricao
 */
function createEvaluatedContactCandidate(
    SelectionProcess $process,
    string $name,
    string $cpf,
    string $email,
    string $protocol,
    ?array $dadosInscricao = null,
    string $evaluationStatus = 'concluida',
): Application {
    Role::findOrCreate('candidato', 'web');
    Role::findOrCreate('avaliador', 'web');

    $candidate = User::factory()->create([
        'name' => $name,
        'cpf' => $cpf,
        'email' => $email,
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
        'pontuacao_total' => 15.0,
        'concluida_em' => $evaluationStatus === EvaluationStatus::Concluida->value ? now() : null,
    ]);

    return $application;
}

test('guest is redirected from candidate contacts report', function (): void {
    $this->get(route('admin.reports.contacts'))
        ->assertRedirect(route('login'));
});

test('non-admin cannot access candidate contacts report', function (): void {
    Role::findOrCreate('candidato', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $this->actingAs($candidate)
        ->get(route('admin.reports.contacts'))
        ->assertForbidden();
});

test('admin sees evaluated candidate contacts with codigo nome cpf and email', function (): void {
    $admin = createContactsReportAdmin();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Contatos 2026',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    createEvaluatedContactCandidate(
        $process,
        'Maria Contato',
        '52998224725',
        'maria.contato@example.com',
        '2026-8001',
    );

    $this->actingAs($admin)
        ->get(route('admin.reports.contacts'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reports/Contacts')
            ->has('candidates.data', 1)
            ->where('candidates.data.0.numero_protocolo', '2026-8001')
            ->where('candidates.data.0.nome_completo', 'Maria Contato')
            ->where('candidates.data.0.cpf_mascarado', '529.***.***-25')
            ->where('candidates.data.0.email', 'maria.contato@example.com')
            ->missing('candidates.data.0.cpf')
            ->has('filterOptions.processes')
        );
});

test('candidate contacts report excludes enrolled candidates without concluded evaluation', function (): void {
    $admin = createContactsReportAdmin();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Sem Avaliação Contatos',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    Role::findOrCreate('candidato', 'web');

    $enrolledOnly = User::factory()->create([
        'name' => 'Somente Inscrito',
        'cpf' => '39053344705',
        'email' => 'inscrito@example.com',
        'email_verified_at' => now(),
    ]);
    $enrolledOnly->assignRole('candidato');

    Application::query()->create([
        'user_id' => $enrolledOnly->id,
        'selection_process_id' => $process->id,
        'status' => ApplicationStatus::Inscrita->value,
        'numero_protocolo' => '2026-8002',
        'finalizada_em' => now(),
        'dados_inscricao' => [
            'step_3' => validApplicationStep3Payload(),
        ],
    ]);

    createEvaluatedContactCandidate(
        $process,
        'Já Avaliado',
        '11144477735',
        'avaliado@example.com',
        '2026-8003',
    );

    createEvaluatedContactCandidate(
        $process,
        'Ainda Em Análise',
        '15350946056',
        'emanalise@example.com',
        '2026-8004',
        null,
        EvaluationStatus::EmAnalise->value,
    );

    $this->actingAs($admin)
        ->get(route('admin.reports.contacts'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('candidates.data', 1)
            ->where('candidates.data.0.nome_completo', 'Já Avaliado')
            ->where('candidates.data.0.email', 'avaliado@example.com')
        );
});

test('admin can filter candidate contacts by selection process', function (): void {
    $admin = createContactsReportAdmin();

    $processA = SelectionProcess::query()->create([
        'titulo' => 'Processo A Contatos',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    $processB = SelectionProcess::query()->create([
        'titulo' => 'Processo B Contatos',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'tipo_programa' => 'doutorado',
    ]);

    createEvaluatedContactCandidate(
        $processA,
        'Ana Processo A',
        '52998224725',
        'ana.a@example.com',
        '2026-8101',
    );

    createEvaluatedContactCandidate(
        $processB,
        'Bruno Processo B',
        '39053344705',
        'bruno.b@example.com',
        '2026-8102',
    );

    $this->actingAs($admin)
        ->get(route('admin.reports.contacts', [
            'selection_process_id' => $processA->id,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reports/Contacts')
            ->has('candidates.data', 1)
            ->where('candidates.data.0.numero_protocolo', '2026-8101')
            ->where('candidates.data.0.nome_completo', 'Ana Processo A')
            ->where('candidates.data.0.email', 'ana.a@example.com')
            ->where('filters.selection_process_id', $processA->id)
        );
});

test('candidate contacts report is ordered by candidate name', function (): void {
    $admin = createContactsReportAdmin();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Ordenação Contatos',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    createEvaluatedContactCandidate(
        $process,
        'Zélia Contato',
        '39053344705',
        'zelia@example.com',
        '2026-8201',
    );

    createEvaluatedContactCandidate(
        $process,
        'Ana Contato',
        '11144477735',
        'ana@example.com',
        '2026-8202',
    );

    createEvaluatedContactCandidate(
        $process,
        'Marcos Contato',
        '15350946056',
        'marcos@example.com',
        '2026-8203',
    );

    $this->actingAs($admin)
        ->get(route('admin.reports.contacts'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('candidates.data.0.nome_completo', 'Ana Contato')
            ->where('candidates.data.1.nome_completo', 'Marcos Contato')
            ->where('candidates.data.2.nome_completo', 'Zélia Contato')
        );
});

test('admin can generate candidate contacts print pdf with filters', function (): void {
    $admin = createContactsReportAdmin();

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo PDF Contatos',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    createEvaluatedContactCandidate(
        $process,
        'Paula Contato',
        '15350946056',
        'paula@example.com',
        '2026-8301',
    );

    $this->actingAs($admin)
        ->get(route('admin.reports.contacts.print', [
            'selection_process_id' => $process->id,
        ]))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});

test('non-admin cannot print candidate contacts report', function (): void {
    Role::findOrCreate('candidato', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $this->actingAs($candidate)
        ->get(route('admin.reports.contacts.print'))
        ->assertForbidden();
});

test('candidate contacts pdf filter labels describe active filters', function (): void {
    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Labels Contatos',
        'descricao' => 'Descrição',
        'status' => 'ativo',
        'tipo_programa' => 'mestrado',
    ]);

    $labels = app(CandidateContactsReportService::class)
        ->activeFilterLabels([
            'selection_process_id' => $process->id,
        ]);

    expect($labels)->toBe([
        'Processo seletivo: Processo Labels Contatos',
    ]);
});
