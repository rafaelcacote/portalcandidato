<?php

use App\Models\Modules\Admin\Models\ProcessTitleGroup;
use App\Models\Modules\Admin\Models\ProcessTitleItem;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Models\User;
use App\Modules\Admin\Services\SplitLegacyC1D4TitleItemsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createLegacyTitleProcessWithC1AndD4(): SelectionProcess
{
    $process = SelectionProcess::query()->create([
        'titulo' => 'PS Legado C1 D4',
        'descricao' => 'D',
        'status' => 'ativo',
    ]);

    $groupC = ProcessTitleGroup::query()->create([
        'selection_process_id' => $process->id,
        'code' => 'C',
        'name' => 'Produção Bibliográfica',
        'max_score' => 2.0,
        'order' => 3,
        'is_active' => true,
    ]);

    ProcessTitleItem::query()->create([
        'process_title_group_id' => $groupC->id,
        'code' => 'C1',
        'title' => 'Artigo científico (legado)',
        'score_per_unit' => 0.50,
        'score_unit' => 'por artigo',
        'requires_attachment' => true,
        'max_file_size_mb' => 10,
        'order' => 1,
        'is_active' => true,
    ]);

    ProcessTitleItem::query()->create([
        'process_title_group_id' => $groupC->id,
        'code' => 'C2',
        'title' => 'Livro',
        'score_per_unit' => 0.40,
        'score_unit' => 'por título',
        'requires_attachment' => true,
        'max_file_size_mb' => 10,
        'order' => 2,
        'is_active' => true,
    ]);

    $groupD = ProcessTitleGroup::query()->create([
        'selection_process_id' => $process->id,
        'code' => 'D',
        'name' => 'Participação em Eventos',
        'max_score' => 0.5,
        'order' => 4,
        'is_active' => true,
    ]);

    ProcessTitleItem::query()->create([
        'process_title_group_id' => $groupD->id,
        'code' => 'D4',
        'title' => 'Ouvinte em eventos (legado)',
        'score_per_unit' => 0.01,
        'score_unit' => 'por evento',
        'requires_attachment' => true,
        'max_file_size_mb' => 10,
        'order' => 4,
        'is_active' => true,
    ]);

    return $process;
}

test('split service creates subitems and remaps documents from legacy C1 and D4', function (): void {
    $process = createLegacyTitleProcessWithC1AndD4();

    $legacyC1 = ProcessTitleItem::query()
        ->whereHas('titleGroup', fn ($q) => $q->where('selection_process_id', $process->id))
        ->where('code', 'C1')
        ->firstOrFail();

    $legacyD4 = ProcessTitleItem::query()
        ->whereHas('titleGroup', fn ($q) => $q->where('selection_process_id', $process->id))
        ->where('code', 'D4')
        ->firstOrFail();

    $application = Application::query()->create(evaluableApplicationAttributes([
        'user_id' => User::factory()->create()->id,
        'selection_process_id' => $process->id,
        'status' => 'em_analise',
    ]));

    $c1Document = ApplicationDocument::query()->create([
        'application_id' => $application->id,
        'process_title_item_id' => $legacyC1->id,
        'quantidade' => 1,
        'caminho' => 'private/test/c1.pdf',
        'nome_arquivo' => 'c1.pdf',
        'mime' => 'application/pdf',
        'status' => 'enviado',
    ]);

    $d4Document = ApplicationDocument::query()->create([
        'application_id' => $application->id,
        'process_title_item_id' => $legacyD4->id,
        'quantidade' => 1,
        'caminho' => 'private/test/d4.pdf',
        'nome_arquivo' => 'd4.pdf',
        'mime' => 'application/pdf',
        'status' => 'enviado',
    ]);

    $stats = app(SplitLegacyC1D4TitleItemsService::class)->splitAll($process->id);

    expect($stats)->toBe([
        'processes' => 1,
        'c1_splits' => 1,
        'd4_splits' => 1,
        'documents_migrated' => 2,
    ]);

    $legacyC1->refresh();
    $legacyD4->refresh();

    expect($legacyC1->is_active)->toBeFalse()
        ->and($legacyD4->is_active)->toBeFalse();

    $c11 = ProcessTitleItem::query()
        ->where('process_title_group_id', $legacyC1->process_title_group_id)
        ->where('code', 'C1.1')
        ->firstOrFail();

    $d43 = ProcessTitleItem::query()
        ->where('process_title_group_id', $legacyD4->process_title_group_id)
        ->where('code', 'D4.3')
        ->firstOrFail();

    expect($c1Document->refresh()->process_title_item_id)->toBe($c11->id)
        ->and($d4Document->refresh()->process_title_item_id)->toBe($d43->id);

    $c2 = ProcessTitleItem::query()
        ->where('process_title_group_id', $legacyC1->process_title_group_id)
        ->where('code', 'C2')
        ->firstOrFail();

    expect($c2->order)->toBe(3);
});

test('split service is idempotent when subitems already exist', function (): void {
    $process = createLegacyTitleProcessWithC1AndD4();

    $service = app(SplitLegacyC1D4TitleItemsService::class);

    $first = $service->splitAll($process->id);
    $second = $service->splitAll($process->id);

    expect($first['c1_splits'])->toBe(1)
        ->and($second['c1_splits'])->toBe(0)
        ->and($second['d4_splits'])->toBe(0)
        ->and($second['documents_migrated'])->toBe(0);
});

test('artisan command splits legacy title items', function (): void {
    $process = createLegacyTitleProcessWithC1AndD4();

    $this->artisan('titles:split-c1-d4', ['--process' => (string) $process->id])
        ->assertSuccessful();

    expect(
        ProcessTitleItem::query()
            ->whereHas('titleGroup', fn ($q) => $q->where('selection_process_id', $process->id))
            ->where('code', 'C1.1')
            ->exists(),
    )->toBeTrue()
        ->and(
            ProcessTitleItem::query()
                ->whereHas('titleGroup', fn ($q) => $q->where('selection_process_id', $process->id))
                ->where('code', 'D4.2')
                ->value('score_per_unit'),
        )->toBe('0.03');
});
