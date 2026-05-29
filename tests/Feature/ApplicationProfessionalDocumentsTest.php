<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationAppeal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function finalizedCandidateApplication(): array
{
    Role::findOrCreate('candidato', 'web');

    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Teste',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    $stage = $process->stages()->create([
        'nome' => 'Inscrição',
        'ordem' => 1,
        'fim_em' => now()->subDay(),
        'recurso_inicio_em' => now()->subHours(12),
        'recurso_fim_em' => now()->addDays(3),
    ]);

    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'inscrita',
        'numero_protocolo' => 'PS-2026-000001',
        'finalizada_em' => now(),
    ]);

    return compact('candidate', 'process', 'application', 'stage');
}

test('finalized application show includes professional documents and appeals props', function () {
    ['candidate' => $candidate, 'application' => $application] = finalizedCandidateApplication();

    $this->actingAs($candidate)
        ->get(route('candidate.applications.show', $application))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('professionalDocuments', 2)
            ->has('appealStages', 1)
            ->where('hasOpenRecursoWindow', true),
        );
});

test('candidate can download inscription receipt pdf', function () {
    ['candidate' => $candidate, 'application' => $application] = finalizedCandidateApplication();

    $this->actingAs($candidate)
        ->get(route('candidate.applications.documents.comprovante', $application))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('candidate cannot download receipt before finalizing', function () {
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $process = SelectionProcess::query()->create([
        'titulo' => 'Outro processo',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    $draft = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'rascunho',
    ]);

    $this->actingAs($candidate)
        ->get(route('candidate.applications.documents.comprovante', $draft))
        ->assertForbidden();
});

test('candidate can submit appeal during open window', function () {
    ['candidate' => $candidate, 'application' => $application, 'stage' => $stage] = finalizedCandidateApplication();

    $this->actingAs($candidate)
        ->post(route('candidate.applications.appeals.store', $application), [
            'process_stage_id' => $stage->id,
            'texto' => str_repeat('Solicito revisão da decisão desta etapa. ', 3),
        ])
        ->assertRedirect(route('candidate.applications.show', $application));

    expect(ApplicationAppeal::query()->count())->toBe(1);
});

test('candidate cannot submit duplicate appeal for same stage', function () {
    ['candidate' => $candidate, 'application' => $application, 'stage' => $stage] = finalizedCandidateApplication();

    ApplicationAppeal::query()->create([
        'application_id' => $application->id,
        'process_stage_id' => $stage->id,
        'texto' => 'Recurso anterior já enviado para testes.',
        'status' => 'enviado',
    ]);

    $this->actingAs($candidate)
        ->post(route('candidate.applications.appeals.store', $application), [
            'process_stage_id' => $stage->id,
            'texto' => str_repeat('Novo recurso não permitido nesta etapa. ', 3),
        ])
        ->assertSessionHasErrors('process_stage_id');
});

test('candidate cannot submit appeal when window is closed', function () {
    ['candidate' => $candidate, 'application' => $application, 'stage' => $stage] = finalizedCandidateApplication();

    $stage->update([
        'recurso_inicio_em' => now()->subDays(10),
        'recurso_fim_em' => now()->subDays(5),
    ]);

    $this->actingAs($candidate)
        ->post(route('candidate.applications.appeals.store', $application), [
            'process_stage_id' => $stage->id,
            'texto' => str_repeat('Recurso fora do prazo não deve ser aceito. ', 3),
        ])
        ->assertSessionHasErrors('process_stage_id');
});

test('applications index exposes comprovante url for finalized applications', function () {
    ['candidate' => $candidate] = finalizedCandidateApplication();

    $this->actingAs($candidate)
        ->get(route('candidate.applications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('applications.data', 1)
            ->where('applications.data.0.comprovante_url', fn ($url) => is_string($url) && $url !== ''),
        );
});
