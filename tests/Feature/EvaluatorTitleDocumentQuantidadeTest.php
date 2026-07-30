<?php

use App\Models\Modules\Admin\Models\ProcessTitleGroup;
use App\Models\Modules\Admin\Models\ProcessTitleItem;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Models\Modules\Evaluator\Models\ApplicationEvaluation;
use App\Models\Modules\Evaluator\Models\ApplicationEvaluationDocumentScore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Application, 2: ApplicationDocument}
 */
function createPeriodTitleScenario(float $scorePerUnit = 0.40, string $scoreUnit = 'por ano de exercício', ?int $maxQuantity = 5): array
{
    Role::findOrCreate('candidato', 'web');
    Role::findOrCreate('avaliador', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $evaluator = User::factory()->create(['email_verified_at' => now()]);
    $evaluator->assignRole('avaliador');

    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Doutorado',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    $group = ProcessTitleGroup::query()->create([
        'selection_process_id' => $process->id,
        'code' => 'B',
        'name' => 'Atuação Profissional',
        'max_score' => 3.50,
        'is_active' => true,
    ]);

    $item = ProcessTitleItem::query()->create([
        'process_title_group_id' => $group->id,
        'code' => 'B1',
        'title' => 'Atividade de Enfermagem Assistencial',
        'score_per_unit' => $scorePerUnit,
        'score_unit' => $scoreUnit,
        'max_quantity' => $maxQuantity,
        'requires_attachment' => true,
        'max_file_size_mb' => 10,
        'is_active' => true,
    ]);

    $application = Application::query()->create(evaluableApplicationAttributes([
        'user_id' => $candidate->id,
        'selection_process_id' => $process->id,
        'status' => 'em_analise',
    ]));

    $document = ApplicationDocument::query()->create([
        'application_id' => $application->id,
        'process_required_document_id' => null,
        'process_title_item_id' => $item->id,
        'quantidade' => 1,
        'caminho' => 'private/test/doc.pdf',
        'nome_arquivo' => 'doc.pdf',
        'mime' => 'application/pdf',
        'status' => 'enviado',
    ]);

    return [$evaluator, $application, $document];
}

test('evaluator can approve title document with period quantidade and score multiplies', function (): void {
    [$evaluator, $application, $document] = createPeriodTitleScenario();

    $this->actingAs($evaluator)
        ->post(route('evaluator.candidates.documents.decision', [$application, $document]), [
            'status' => 'aprovado',
            'quantidade' => 2,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $document->refresh();
    expect($document->quantidade)->toBe(2)
        ->and($document->status)->toBe('aprovado');

    $evaluation = ApplicationEvaluation::query()->firstOrFail();
    expect((float) $evaluation->pontuacao_total)->toBe(0.80)
        ->and((float) ApplicationEvaluationDocumentScore::query()->firstOrFail()->pontuacao)->toBe(0.80);
});

test('evaluator can update quantidade on approved title document and score recalculates', function (): void {
    [$evaluator, $application, $document] = createPeriodTitleScenario();

    $this->actingAs($evaluator)
        ->post(route('evaluator.candidates.documents.decision', [$application, $document]), [
            'status' => 'aprovado',
            'quantidade' => 1,
        ])
        ->assertRedirect();

    expect((float) ApplicationEvaluation::query()->firstOrFail()->pontuacao_total)->toBe(0.40);

    $this->actingAs($evaluator)
        ->patch(route('evaluator.candidates.documents.quantidade', [$application, $document]), [
            'quantidade' => 3,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $document->refresh();
    expect($document->quantidade)->toBe(3);

    $evaluation = ApplicationEvaluation::query()->firstOrFail();
    expect((float) $evaluation->pontuacao_total)->toBe(1.20)
        ->and((float) ApplicationEvaluationDocumentScore::query()->firstOrFail()->pontuacao)->toBe(1.20);
});

test('evaluator can set quantidade before approval without creating score yet', function (): void {
    [$evaluator, $application, $document] = createPeriodTitleScenario(
        scorePerUnit: 0.20,
        scoreUnit: 'por semestre',
        maxQuantity: 10,
    );

    $this->actingAs($evaluator)
        ->patch(route('evaluator.candidates.documents.quantidade', [$application, $document]), [
            'quantidade' => 4,
        ])
        ->assertRedirect();

    $document->refresh();
    expect($document->quantidade)->toBe(4)
        ->and(ApplicationEvaluationDocumentScore::query()->count())->toBe(0);

    $this->actingAs($evaluator)
        ->post(route('evaluator.candidates.documents.decision', [$application, $document]), [
            'status' => 'aprovado',
        ])
        ->assertRedirect();

    expect((float) ApplicationEvaluationDocumentScore::query()->firstOrFail()->pontuacao)->toBe(0.80);
});

test('evaluator cannot set quantidade above max_quantity from edital', function (): void {
    [$evaluator, $application, $document] = createPeriodTitleScenario();

    $this->actingAs($evaluator)
        ->patch(route('evaluator.candidates.documents.quantidade', [$application, $document]), [
            'quantidade' => 6,
        ])
        ->assertSessionHasErrors('quantidade');

    expect($document->fresh()->quantidade)->toBe(1);
});

test('evaluator cannot set quantidade on non-title document', function (): void {
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
        'status' => 'em_analise',
    ]));

    $document = ApplicationDocument::query()->create([
        'application_id' => $application->id,
        'process_required_document_id' => null,
        'process_title_item_id' => null,
        'caminho' => 'private/test/rg.pdf',
        'nome_arquivo' => 'rg.pdf',
        'mime' => 'application/pdf',
        'status' => 'enviado',
    ]);

    $this->actingAs($evaluator)
        ->patch(route('evaluator.candidates.documents.quantidade', [$application, $document]), [
            'quantidade' => 2,
        ])
        ->assertSessionHasErrors('quantidade');
});
