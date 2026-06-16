<?php

namespace App\Modules\Admin\Services;

use App\Models\Modules\Admin\Models\ProcessTitleGroup;
use App\Models\Modules\Admin\Models\ProcessTitleItem;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use Illuminate\Support\Facades\DB;

class SplitLegacyC1D4TitleItemsService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function c1SplitItemDefinitions(): array
    {
        return [
            [
                'code' => 'C1.1',
                'title' => 'Publicação de Artigo Científico nas áreas de Enfermagem ou de Saúde Pública, em revistas indexadas (com ISSN) – Qualis Referência (2021-2024: A1 a A4)',
                'score_per_unit' => 0.50,
                'score_unit' => 'por artigo',
                'max_quantity' => null,
                'period_rule' => 'Últimos 5 anos',
                'candidate_instructions' => 'Classificação Qualis A1, A2, A3 ou A4.',
            ],
            [
                'code' => 'C1.2',
                'title' => 'Publicação de Artigo Científico nas áreas de Enfermagem ou de Saúde Pública, em revistas indexadas (com ISSN) – Qualis Referência (2021-2024: B1 a B4)',
                'score_per_unit' => 0.30,
                'score_unit' => 'por artigo',
                'max_quantity' => null,
                'period_rule' => 'Últimos 5 anos',
                'candidate_instructions' => 'Classificação Qualis B1, B2, B3 ou B4.',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function d4SplitItemDefinitions(): array
    {
        return [
            [
                'code' => 'D4.1',
                'title' => 'Participação como ouvinte em eventos científicos (evento local e regional)',
                'score_per_unit' => 0.01,
                'score_unit' => 'por evento',
                'max_quantity' => null,
                'period_rule' => 'Últimos 5 anos',
                'candidate_instructions' => null,
            ],
            [
                'code' => 'D4.2',
                'title' => 'Participação como ouvinte em eventos científicos (evento nacional)',
                'score_per_unit' => 0.03,
                'score_unit' => 'por evento',
                'max_quantity' => null,
                'period_rule' => 'Últimos 5 anos',
                'candidate_instructions' => null,
            ],
            [
                'code' => 'D4.3',
                'title' => 'Participação como ouvinte em eventos científicos (evento internacional)',
                'score_per_unit' => 0.05,
                'score_unit' => 'por evento',
                'max_quantity' => null,
                'period_rule' => 'Últimos 5 anos',
                'candidate_instructions' => null,
            ],
        ];
    }

    /**
     * @return array{processes: int, c1_splits: int, d4_splits: int, documents_migrated: int}
     */
    public function splitAll(?int $selectionProcessId = null): array
    {
        $stats = [
            'processes' => 0,
            'c1_splits' => 0,
            'd4_splits' => 0,
            'documents_migrated' => 0,
        ];

        $query = SelectionProcess::query()->orderBy('id');

        if ($selectionProcessId !== null) {
            $query->whereKey($selectionProcessId);
        }

        $query->each(function (SelectionProcess $process) use (&$stats): void {
            $result = $this->splitForProcess($process);

            if ($result['c1_split'] || $result['d4_split']) {
                $stats['processes']++;
            }

            if ($result['c1_split']) {
                $stats['c1_splits']++;
            }

            if ($result['d4_split']) {
                $stats['d4_splits']++;
            }

            $stats['documents_migrated'] += $result['documents_migrated'];
        });

        return $stats;
    }

    /**
     * @return array{c1_split: bool, d4_split: bool, documents_migrated: int}
     */
    public function splitForProcess(SelectionProcess $process): array
    {
        $result = [
            'c1_split' => false,
            'd4_split' => false,
            'documents_migrated' => 0,
        ];

        DB::transaction(function () use ($process, &$result): void {
            $groupC = ProcessTitleGroup::query()
                ->where('selection_process_id', $process->id)
                ->where('code', 'C')
                ->first();

            if ($groupC !== null) {
                $c1Result = $this->splitLegacyItem(
                    $groupC,
                    legacyCode: 'C1',
                    splitDefinitions: $this->c1SplitItemDefinitions(),
                    defaultTargetCode: 'C1.1',
                );

                $result['c1_split'] = $c1Result['split'];
                $result['documents_migrated'] += $c1Result['documents_migrated'];
            }

            $groupD = ProcessTitleGroup::query()
                ->where('selection_process_id', $process->id)
                ->where('code', 'D')
                ->first();

            if ($groupD !== null) {
                $d4Result = $this->splitLegacyItem(
                    $groupD,
                    legacyCode: 'D4',
                    splitDefinitions: $this->d4SplitItemDefinitions(),
                    defaultTargetCode: 'D4.3',
                );

                $result['d4_split'] = $d4Result['split'];
                $result['documents_migrated'] += $d4Result['documents_migrated'];
            }
        });

        return $result;
    }

    /**
     * @param  list<array<string, mixed>>  $splitDefinitions
     * @return array{split: bool, documents_migrated: int}
     */
    private function splitLegacyItem(
        ProcessTitleGroup $group,
        string $legacyCode,
        array $splitDefinitions,
        string $defaultTargetCode,
    ): array {
        $firstSplitCode = $splitDefinitions[0]['code'] ?? null;

        if ($firstSplitCode === null) {
            return ['split' => false, 'documents_migrated' => 0];
        }

        $alreadySplit = ProcessTitleItem::query()
            ->where('process_title_group_id', $group->id)
            ->where('code', $firstSplitCode)
            ->exists();

        if ($alreadySplit) {
            return ['split' => false, 'documents_migrated' => 0];
        }

        $legacyItem = ProcessTitleItem::query()
            ->where('process_title_group_id', $group->id)
            ->where('code', $legacyCode)
            ->first();

        if ($legacyItem === null) {
            return ['split' => false, 'documents_migrated' => 0];
        }

        $legacyOrder = (int) $legacyItem->order;
        $extraSlots = count($splitDefinitions) - 1;

        ProcessTitleItem::query()
            ->where('process_title_group_id', $group->id)
            ->where('order', '>', $legacyOrder)
            ->increment('order', $extraSlots);

        $createdItems = [];

        foreach ($splitDefinitions as $index => $definition) {
            $createdItems[$definition['code']] = ProcessTitleItem::query()->create([
                'process_title_group_id' => $group->id,
                'code' => $definition['code'],
                'title' => $definition['title'],
                'score_per_unit' => $definition['score_per_unit'],
                'score_unit' => $definition['score_unit'],
                'max_quantity' => $definition['max_quantity'],
                'period_rule' => $definition['period_rule'],
                'requires_attachment' => $legacyItem->requires_attachment,
                'accepted_formats' => $legacyItem->accepted_formats,
                'max_file_size_mb' => $legacyItem->max_file_size_mb,
                'candidate_instructions' => $definition['candidate_instructions'],
                'order' => $legacyOrder + $index,
                'is_active' => true,
            ]);
        }

        $defaultTarget = $createdItems[$defaultTargetCode] ?? reset($createdItems);

        $documentsMigrated = ApplicationDocument::query()
            ->where('process_title_item_id', $legacyItem->id)
            ->update(['process_title_item_id' => $defaultTarget->id]);

        $legacyItem->update(['is_active' => false]);

        return [
            'split' => true,
            'documents_migrated' => $documentsMigrated,
        ];
    }
}
