<?php

use App\Mail\RecursoRespondido;
use App\Models\Modules\Admin\Models\ProcessStage;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationAppeal;
use App\Models\User;
use App\Modules\Shared\Enums\ApplicationAppealStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('admin configure page lists process appeals', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    ['process' => $process, 'appeal' => $appeal] = createAppealFixture();

    $this->actingAs($admin)
        ->get(route('admin.processes.show', $process))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('processAppeals', 1)
            ->where('processAppeals.0.id', $appeal->id)
            ->has('appealStatusOptions'),
        );
});

test('admin can respond to appeal with decision and message', function () {
    Mail::fake();

    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    ['process' => $process, 'appeal' => $appeal, 'candidate' => $candidate] = createAppealFixture();

    $this->actingAs($admin)
        ->put(route('admin.processes.appeals.update', [
            'selectionProcess' => $process,
            'applicationAppeal' => $appeal,
        ]), [
            'status' => ApplicationAppealStatus::Deferido->value,
            'resposta' => 'Recurso deferido após reanálise da documentação apresentada.',
        ])
        ->assertRedirect(route('admin.processes.show', $process));

    $appeal->refresh();

    expect($appeal->status)->toBe(ApplicationAppealStatus::Deferido->value)
        ->and($appeal->resposta)->toContain('deferido')
        ->and($appeal->respondido_por)->toBe($admin->id)
        ->and($appeal->respondido_em)->not->toBeNull();

    Mail::assertQueued(RecursoRespondido::class, function (RecursoRespondido $mail) use ($candidate, $appeal): bool {
        return $mail->hasTo($candidate->email)
            && $mail->appeal->is($appeal);
    });
});

test('email is not sent when candidate has no email address', function () {
    Mail::fake();

    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    ['process' => $process, 'appeal' => $appeal, 'candidate' => $candidate] = createAppealFixture();
    $candidate->forceFill(['email' => ''])->save();

    $this->actingAs($admin)
        ->put(route('admin.processes.appeals.update', [
            'selectionProcess' => $process,
            'applicationAppeal' => $appeal,
        ]), [
            'status' => ApplicationAppealStatus::EmAnalise->value,
            'resposta' => 'Seu recurso está em análise pela comissão.',
        ])
        ->assertRedirect(route('admin.processes.show', $process));

    Mail::assertNothingQueued();
});

test('admin must provide response when deferring or denying appeal', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    ['process' => $process, 'appeal' => $appeal] = createAppealFixture();

    $this->actingAs($admin)
        ->put(route('admin.processes.appeals.update', [
            'selectionProcess' => $process,
            'applicationAppeal' => $appeal,
        ]), [
            'status' => ApplicationAppealStatus::Indeferido->value,
        ])
        ->assertSessionHasErrors('resposta');
});

test('admin can update stage recurso deadline fields', function () {
    Role::findOrCreate('admin', 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Recurso',
        'descricao' => 'Teste',
        'status' => 'ativo',
    ]);

    $stage = $process->stages()->create([
        'nome' => 'Prova',
        'ordem' => 1,
        'fim_em' => now()->subDay(),
    ]);

    $this->actingAs($admin)
        ->put(route('admin.processes.stages.update', [
            'selectionProcess' => $process,
            'processStage' => $stage,
        ]), [
            'recurso_inicio_em' => '2026-06-01T08:00',
            'recurso_fim_em' => '2026-06-10T23:59',
        ])
        ->assertRedirect();

    $stage->refresh();

    expect($stage->recurso_inicio_em)->not->toBeNull()
        ->and($stage->recurso_fim_em)->not->toBeNull();
});

test('candidate sees admin response on appeal', function () {
    Role::findOrCreate('candidato', 'web');
    ['process' => $process, 'appeal' => $appeal, 'candidate' => $candidate] = createAppealFixture();

    $appeal->update([
        'status' => ApplicationAppealStatus::Deferido->value,
        'resposta' => 'Seu recurso foi acolhido pela comissão.',
        'respondido_em' => now(),
    ]);

    $application = $appeal->application;

    $this->actingAs($candidate)
        ->get(route('candidate.applications.show', $application))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('appeals.0.resposta', 'Seu recurso foi acolhido pela comissão.'),
        );
});

/**
 * @return array{process: SelectionProcess, appeal: ApplicationAppeal, candidate: User}
 */
function createAppealFixture(): array
{
    Role::findOrCreate('candidato', 'web');
    $candidate = User::factory()
        ->completeCandidateProfile()
        ->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo Recursos',
        'descricao' => 'Teste',
        'status' => 'ativo',
    ]);

    $stage = ProcessStage::query()->create([
        'selection_process_id' => $process->id,
        'nome' => 'Resultado',
        'ordem' => 1,
        'fim_em' => now()->subDay(),
        'recurso_inicio_em' => now()->subHours(6),
        'recurso_fim_em' => now()->addDays(2),
    ]);

    $application = Application::query()->create([
        'selection_process_id' => $process->id,
        'user_id' => $candidate->id,
        'status' => 'inscrita',
        'numero_protocolo' => 'PS-2026-000099',
        'finalizada_em' => now(),
    ]);

    $appeal = ApplicationAppeal::query()->create([
        'application_id' => $application->id,
        'process_stage_id' => $stage->id,
        'texto' => 'Solicito revisão da nota atribuída na etapa de prova escrita.',
        'status' => ApplicationAppealStatus::Enviado->value,
    ]);

    return compact('process', 'appeal', 'candidate');
}
