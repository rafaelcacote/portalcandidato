<?php

use App\Models\Modules\Admin\Models\ProcessRequiredDocument;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Models\User;
use App\Modules\Shared\Enums\ApplicationStatus;
use App\Modules\Shared\Enums\DocumentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('guest is redirected from candidate dashboard', function (): void {
    $this->get(route('candidate.dashboard'))
        ->assertRedirect(route('login'));
});

test('non-candidate cannot access candidate dashboard', function (): void {
    Role::findOrCreate('admin', 'web');

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('candidate.dashboard'))
        ->assertForbidden();
});

test('candidate sees dashboard inertia props', function (): void {
    Role::findOrCreate('candidato', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $this->actingAs($candidate)
        ->get(route('candidate.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Dashboard')
            ->has('ui', fn (Assert $ui) => $ui
                ->where('settings', __('ui.settings', [], 'pt_BR'))
                ->where('log_out', __('ui.log_out', [], 'pt_BR')))
            ->has('summary', fn (Assert $summary) => $summary
                ->where('inscricoes_em_andamento', 0)
                ->where('pendencias', 0)
                ->where('mensagens_nao_lidas', 0)
            )
            ->has('inscricoes_em_andamento', 0)
            ->has('pendencias_inscricao', 0)
            ->has('documentos_recusados', 0));
});

test('dashboard summary counts applications and rejected documents', function (): void {
    Role::findOrCreate('candidato', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $processA = SelectionProcess::query()->create([
        'titulo' => 'Edital A',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    $processB = SelectionProcess::query()->create([
        'titulo' => 'Edital B',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    $draft = Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $processA->id,
        'status' => ApplicationStatus::Rascunho->value,
    ]);

    Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $processB->id,
        'status' => ApplicationStatus::Pendencia->value,
    ]);

    $required = ProcessRequiredDocument::query()->create([
        'selection_process_id' => $processA->id,
        'nome' => 'Documento obrigatório',
        'obrigatorio' => true,
    ]);

    ApplicationDocument::query()->create([
        'application_id' => $draft->id,
        'process_required_document_id' => $required->id,
        'caminho' => 'private/documents/test/rg.pdf',
        'nome_arquivo' => 'rg.pdf',
        'mime' => 'application/pdf',
        'status' => DocumentStatus::Recusado->value,
    ]);

    $this->actingAs($candidate)
        ->get(route('candidate.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Dashboard')
            ->where('summary.inscricoes_em_andamento', 1)
            ->where('summary.pendencias', 2));
});
