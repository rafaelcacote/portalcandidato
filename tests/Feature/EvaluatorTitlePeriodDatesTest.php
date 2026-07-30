<?php

use App\Models\Modules\Admin\Models\ProcessTitleGroup;
use App\Models\Modules\Admin\Models\ProcessTitleItem;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Models\Modules\Evaluator\Models\ApplicationEvaluation;
use App\Models\Modules\Evaluator\Models\ApplicationEvaluationDocumentScore;
use App\Models\User;
use App\Modules\Evaluator\Services\TitlePeriodQuantityCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Application, 2: ApplicationDocument, 3: ProcessTitleItem}
 */
function createB1PeriodScenario(): array
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
        'inscricao_fim_em' => '2026-07-01 23:59:59',
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
        'score_per_unit' => 0.40,
        'score_unit' => 'por ano de exercício',
        'max_quantity' => 5,
        'period_rule' => 'Últimos 5 anos',
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

    return [$evaluator, $application, $document, $item];
}

test('calculator counts only complete years without rounding up', function (): void {
    $calculator = resolve(TitlePeriodQuantityCalculator::class);

    // 23 months → 1 year (does not round to 2)
    expect($calculator->quantityFromDates(
        Carbon::parse('2022-01-01'),
        Carbon::parse('2023-12-01'),
        asSemesters: false,
        windowEnd: Carbon::parse('2026-07-01'),
        windowYears: 5,
    ))->toBe(1);

    // Exactly 24 months → 2 years
    expect($calculator->quantityFromDates(
        Carbon::parse('2022-01-01'),
        Carbon::parse('2024-01-01'),
        asSemesters: false,
        windowEnd: Carbon::parse('2026-07-01'),
        windowYears: 5,
    ))->toBe(2);
});

test('calculator ignores period outside edital window', function (): void {
    $calculator = resolve(TitlePeriodQuantityCalculator::class);

    // Window: 2021-07-01 .. 2026-07-01. Activity entirely before window → 0
    expect($calculator->quantityFromDates(
        Carbon::parse('2018-01-01'),
        Carbon::parse('2020-01-01'),
        asSemesters: false,
        windowEnd: Carbon::parse('2026-07-01'),
        windowYears: 5,
    ))->toBe(0);

    // Partially outside: only the in-window portion counts
    expect($calculator->quantityFromDates(
        Carbon::parse('2019-01-01'),
        Carbon::parse('2023-07-01'),
        asSemesters: false,
        windowEnd: Carbon::parse('2026-07-01'),
        windowYears: 5,
    ))->toBe(2); // clipped start 2021-07-01 → 2023-07-01 = 2 years
});

test('calculator flags when period exceeds the edital 5-year window', function (): void {
    $calculator = resolve(TitlePeriodQuantityCalculator::class);
    $windowEnd = Carbon::parse('2026-07-01'); // window start: 2021-07-01

    expect($calculator->periodExceedsWindow(
        Carbon::parse('2019-01-01'),
        Carbon::parse('2023-07-01'),
        $windowEnd,
        5,
    ))->toBeTrue()
        ->and($calculator->periodExceedsWindow(
            Carbon::parse('2022-01-01'),
            Carbon::parse('2024-01-01'),
            $windowEnd,
            5,
        ))->toBeFalse();
});

test('calculator counts complete semesters only', function (): void {
    $calculator = resolve(TitlePeriodQuantityCalculator::class);

    expect($calculator->quantityFromDates(
        Carbon::parse('2024-01-01'),
        Carbon::parse('2024-12-01'),
        asSemesters: true,
        windowEnd: Carbon::parse('2026-07-01'),
        windowYears: 5,
    ))->toBe(1); // 11 months → 1 semester

    expect($calculator->quantityFromDates(
        Carbon::parse('2024-01-01'),
        Carbon::parse('2025-01-01'),
        asSemesters: true,
        windowEnd: Carbon::parse('2026-07-01'),
        windowYears: 5,
    ))->toBe(2);
});

test('evaluator can approve B1 with period dates and score multiplies by complete years', function (): void {
    [$evaluator, $application, $document] = createB1PeriodScenario();

    $this->actingAs($evaluator)
        ->post(route('evaluator.candidates.documents.decision', [$application, $document]), [
            'status' => 'aprovado',
            'data_inicio' => '2022-01-01',
            'data_fim' => '2024-01-01',
        ])
        ->assertRedirect();

    $document->refresh();
    expect($document->quantidade)->toBe(2)
        ->and($document->data_inicio?->toDateString())->toBe('2022-01-01')
        ->and($document->data_fim?->toDateString())->toBe('2024-01-01');

    expect((float) ApplicationEvaluationDocumentScore::query()->firstOrFail()->pontuacao)->toBe(0.80);
});

test('evaluator can update period and recalculate approved score', function (): void {
    [$evaluator, $application, $document] = createB1PeriodScenario();

    $this->actingAs($evaluator)
        ->post(route('evaluator.candidates.documents.decision', [$application, $document]), [
            'status' => 'aprovado',
            'data_inicio' => '2023-01-01',
            'data_fim' => '2024-01-01',
        ])
        ->assertRedirect();

    expect((float) ApplicationEvaluation::query()->firstOrFail()->pontuacao_total)->toBe(0.40);

    $this->actingAs($evaluator)
        ->patch(route('evaluator.candidates.documents.period', [$application, $document]), [
            'data_inicio' => '2020-01-01',
            'data_fim' => '2025-07-01',
        ])
        ->assertRedirect();

    $document->refresh();
    // Janela: 2021-07-01 .. 2026-07-01 → período cortado = 4 anos inteiros
    expect($document->quantidade)->toBe(4)
        ->and((float) ApplicationEvaluation::query()->firstOrFail()->pontuacao_total)->toBe(1.60);
});

test('summed periods across documents respect max_quantity', function (): void {
    [$evaluator, $application, $document, $item] = createB1PeriodScenario();

    $document2 = ApplicationDocument::query()->create([
        'application_id' => $application->id,
        'process_required_document_id' => null,
        'process_title_item_id' => $item->id,
        'quantidade' => 1,
        'caminho' => 'private/test/doc2.pdf',
        'nome_arquivo' => 'doc2.pdf',
        'mime' => 'application/pdf',
        'status' => 'enviado',
    ]);

    $this->actingAs($evaluator)
        ->post(route('evaluator.candidates.documents.decision', [$application, $document]), [
            'status' => 'aprovado',
            'data_inicio' => '2021-07-01',
            'data_fim' => '2024-07-01', // 3 anos inteiros
        ])
        ->assertRedirect();

    $this->actingAs($evaluator)
        ->post(route('evaluator.candidates.documents.decision', [$application, $document2]), [
            'status' => 'aprovado',
            'data_inicio' => '2021-07-01',
            'data_fim' => '2025-07-01', // 4 anos brutos; restam 2 do teto 5
        ])
        ->assertRedirect();

    $score1 = (float) ApplicationEvaluationDocumentScore::query()
        ->where('application_document_id', $document->id)
        ->value('pontuacao');
    $score2 = (float) ApplicationEvaluationDocumentScore::query()
        ->where('application_document_id', $document2->id)
        ->value('pontuacao');

    // 3 + 2 = 5 anos no teto → 1.20 + 0.80 = 2.00
    expect($score1)->toBe(1.20)
        ->and($score2)->toBe(0.80)
        ->and(round($score1 + $score2, 2))->toBe(2.00);
});

test('period dates are rejected for non-period title codes', function (): void {
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
        'inscricao_fim_em' => '2026-07-01 23:59:59',
    ]);

    $group = ProcessTitleGroup::query()->create([
        'selection_process_id' => $process->id,
        'code' => 'B',
        'name' => 'Atuação',
        'max_score' => 3.50,
        'is_active' => true,
    ]);

    $item = ProcessTitleItem::query()->create([
        'process_title_group_id' => $group->id,
        'code' => 'B4',
        'title' => 'Curso curta duração',
        'score_per_unit' => 0.10,
        'score_unit' => 'por curso',
        'max_quantity' => 5,
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
        'process_title_item_id' => $item->id,
        'quantidade' => 1,
        'caminho' => 'private/test/doc.pdf',
        'nome_arquivo' => 'doc.pdf',
        'mime' => 'application/pdf',
        'status' => 'enviado',
    ]);

    $this->actingAs($evaluator)
        ->patch(route('evaluator.candidates.documents.period', [$application, $document]), [
            'data_inicio' => '2022-01-01',
            'data_fim' => '2024-01-01',
        ])
        ->assertSessionHasErrors('data_inicio');
});
