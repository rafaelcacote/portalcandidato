<?php

namespace App\Modules\Evaluator\Services;

use App\Models\Modules\Admin\Models\ProcessTitleItem;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class TitlePeriodQuantityCalculator
{
    /**
     * Itens do edital cuja quantidade deriva de período (data início/fim).
     *
     * @var list<string>
     */
    public const PERIOD_DATE_CODES = ['B1', 'B2', 'B3', 'B5', 'B6', 'B7'];

    public function normalizeCode(?string $code): string
    {
        return strtoupper(str_replace(['.', ' '], '', trim((string) $code)));
    }

    public function usesPeriodDates(?ProcessTitleItem $item): bool
    {
        if ($item === null) {
            return false;
        }

        return in_array($this->normalizeCode($item->code), self::PERIOD_DATE_CODES, true);
    }

    public function unitIsSemester(?string $scoreUnit): bool
    {
        return str_contains(mb_strtolower((string) $scoreUnit), 'semestre');
    }

    /**
     * Referência da janela "últimos N anos": fim das inscrições do processo,
     * ou finalização da inscrição, ou agora.
     */
    public function windowEnd(Application $application): CarbonInterface
    {
        $application->loadMissing('selectionProcess');

        $processEnd = $application->selectionProcess?->inscricao_fim_em;
        if ($processEnd !== null) {
            return Carbon::parse($processEnd)->startOfDay();
        }

        if ($application->finalizada_em !== null) {
            return Carbon::parse($application->finalizada_em)->startOfDay();
        }

        return now()->startOfDay();
    }

    public function windowYearsFromPeriodRule(?string $periodRule): int
    {
        if ($periodRule !== null && preg_match('/(\d+)\s*anos?/iu', $periodRule, $matches) === 1) {
            return max(1, (int) $matches[1]);
        }

        return 5;
    }

    /**
     * Início da janela "últimos N anos" do edital, a partir da data de referência.
     */
    public function windowStartFor(CarbonInterface $windowEnd, int $windowYears = 5): CarbonInterface
    {
        return Carbon::parse($windowEnd)->startOfDay()->subYears($windowYears);
    }

    /**
     * Indica se parte do período informado está fora da janela dos últimos N
     * anos do edital (usado para avisar o avaliador na tela).
     */
    public function periodExceedsWindow(
        CarbonInterface $dataInicio,
        CarbonInterface $dataFim,
        CarbonInterface $windowEnd,
        int $windowYears = 5,
    ): bool {
        $windowStart = $this->windowStartFor($windowEnd, $windowYears);
        $windowEndDay = Carbon::parse($windowEnd)->startOfDay();
        $start = Carbon::parse($dataInicio)->startOfDay();
        $end = Carbon::parse($dataFim)->startOfDay();

        return $start->lt($windowStart) || $end->gt($windowEndDay);
    }

    /**
     * Quantidade inteira (anos ou semestres) a partir das datas,
     * cortando o que estiver fora da janela do edital (últimos N anos).
     * Sem arredondamento (só inteiros).
     */
    public function quantityFromDates(
        CarbonInterface $dataInicio,
        CarbonInterface $dataFim,
        bool $asSemesters,
        CarbonInterface $windowEnd,
        int $windowYears = 5,
    ): int {
        $windowEndDay = Carbon::parse($windowEnd)->startOfDay();
        $windowStart = $this->windowStartFor($windowEndDay, $windowYears);
        $start = Carbon::parse($dataInicio)->startOfDay();
        $end = Carbon::parse($dataFim)->startOfDay();

        if ($end->lt($start)) {
            return 0;
        }

        $clippedStart = $start->greaterThan($windowStart) ? $start->copy() : $windowStart->copy();
        $clippedEnd = $end->lessThan($windowEndDay) ? $end->copy() : $windowEndDay->copy();

        if ($clippedStart->greaterThanOrEqualTo($clippedEnd)) {
            return 0;
        }

        $interval = $clippedStart->diff($clippedEnd);
        $totalMonths = ((int) $interval->y * 12) + (int) $interval->m;

        if ($asSemesters) {
            return intdiv($totalMonths, 6);
        }

        return intdiv($totalMonths, 12);
    }

    /**
     * Quantidade bruta do comprovante (antes do rateio do teto entre comprovantes do mesmo item).
     */
    public function rawQuantityForDocument(ApplicationDocument $document, Application $application): int
    {
        $document->loadMissing('titleItem');
        $item = $document->titleItem;

        if ($item === null) {
            return max(0, (int) ($document->quantidade ?? 0));
        }

        if (
            $this->usesPeriodDates($item)
            && $document->data_inicio !== null
            && $document->data_fim !== null
        ) {
            return $this->quantityFromDates(
                Carbon::parse($document->data_inicio),
                Carbon::parse($document->data_fim),
                $this->unitIsSemester($item->score_unit),
                $this->windowEnd($application),
                $this->windowYearsFromPeriodRule($item->period_rule),
            );
        }

        if ($this->usesPeriodDates($item)) {
            return max(0, (int) ($document->quantidade ?? 0));
        }

        return max(1, (int) ($document->quantidade ?? 1));
    }

    /**
     * Quantidade efetiva do comprovante após somar períodos do mesmo item
     * e respeitar max_quantity (rateio estável por id crescente).
     */
    public function effectiveQuantityForDocument(ApplicationDocument $document, Application $application): int
    {
        $document->loadMissing('titleItem');
        $item = $document->titleItem;

        if ($item === null) {
            return 0;
        }

        $raw = $this->rawQuantityForDocument($document, $application);
        $maxQuantity = $item->max_quantity;

        if ($maxQuantity === null) {
            return $raw;
        }

        $maxQuantity = (int) $maxQuantity;

        $siblings = ApplicationDocument::query()
            ->where('application_id', $application->id)
            ->where('process_title_item_id', $document->process_title_item_id)
            ->where('status', '!=', 'recusado')
            ->orderBy('id')
            ->get();

        $remaining = $maxQuantity;

        foreach ($siblings as $sibling) {
            $siblingRaw = $sibling->id === $document->id
                ? $raw
                : $this->rawQuantityForDocument($sibling, $application);

            $allocated = min($siblingRaw, $remaining);
            $remaining -= $allocated;

            if ($sibling->id === $document->id) {
                return $allocated;
            }
        }

        return 0;
    }
}
