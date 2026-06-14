<?php

use App\Models\Modules\Admin\Models\ProcessCriteria;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Models\Modules\Evaluator\Models\ApplicationEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('evaluator can view draft application in read-only mode', function (): void {
    Role::findOrCreate('candidato', 'web');
    Role::findOrCreate('avaliador', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $evaluator = User::factory()->create(['email_verified_at' => now()]);
    $evaluator->assignRole('avaliador');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    $application = Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => 'rascunho',
    ]);

    $this->actingAs($evaluator)
        ->get(route('evaluator.candidates.show', $application))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('can_evaluate', false)
            ->where('application.status', 'rascunho'));
});

test('evaluator can evaluate finalized application', function (): void {
    Role::findOrCreate('candidato', 'web');
    Role::findOrCreate('avaliador', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $evaluator = User::factory()->create(['email_verified_at' => now()]);
    $evaluator->assignRole('avaliador');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    $application = Application::query()->create(evaluableApplicationAttributes([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => 'inscrita',
    ]));

    $this->actingAs($evaluator)
        ->get(route('evaluator.candidates.show', $application))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('can_evaluate', true)
            ->where('application.status', 'inscrita'));
});

test('evaluator cannot score draft application', function (): void {
    Role::findOrCreate('candidato', 'web');
    Role::findOrCreate('avaliador', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $evaluator = User::factory()->create(['email_verified_at' => now()]);
    $evaluator->assignRole('avaliador');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    $criteria = ProcessCriteria::query()->create([
        'selection_process_id' => $process->id,
        'nome' => 'Experiência',
        'peso' => 1,
        'pontuacao_max' => 30,
        'ordem' => 1,
    ]);

    $application = Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => 'rascunho',
    ]);

    $this->actingAs($evaluator)
        ->post(route('evaluator.candidates.score.store', $application), [
            'scores' => [
                [
                    'process_criteria_id' => $criteria->id,
                    'pontuacao' => 20,
                ],
            ],
            'document_scores' => [],
            'resultado' => 'classificado',
        ])
        ->assertForbidden();

    expect(ApplicationEvaluation::query()->count())->toBe(0);
});

test('evaluator cannot decide documents on draft application', function (): void {
    Role::findOrCreate('candidato', 'web');
    Role::findOrCreate('avaliador', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $evaluator = User::factory()->create(['email_verified_at' => now()]);
    $evaluator->assignRole('avaliador');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    $application = Application::query()->create([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => 'rascunho',
    ]);

    $document = ApplicationDocument::query()->create([
        'application_id' => $application->id,
        'process_required_document_id' => null,
        'process_title_item_id' => null,
        'caminho' => 'private/test/doc.pdf',
        'nome_arquivo' => 'doc.pdf',
        'mime' => 'application/pdf',
        'status' => 'enviado',
    ]);

    $this->actingAs($evaluator)
        ->post(route('evaluator.candidates.documents.decision', [$application, $document]), [
            'status' => 'aprovado',
        ])
        ->assertForbidden();

    expect($document->fresh()->status)->toBe('enviado');
});
