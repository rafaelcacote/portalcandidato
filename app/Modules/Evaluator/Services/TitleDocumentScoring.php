<?php

namespace App\Modules\Evaluator\Services;

use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Models\Modules\Evaluator\Models\ApplicationEvaluation;
use App\Models\Modules\Evaluator\Models\ApplicationEvaluationDocumentScore;

class TitleDocumentScoring
{
    public function __construct(
        private readonly TitlePeriodQuantityCalculator $periodQuantityCalculator,
    ) {}

    public function quantityForRow(ApplicationDocument $document, Application $application): int
    {
        return $this->periodQuantityCalculator->effectiveQuantityForDocument($document, $application);
    }

    public function maxPointsForRow(ApplicationDocument $document, ?Application $application = null): float
    {
        $document->loadMissing(['titleItem', 'application']);

        $item = $document->titleItem;
        if ($item === null) {
            return 0.0;
        }

        $application ??= $document->application;
        if ($application === null) {
            return 0.0;
        }

        $perUnit = (float) $item->score_per_unit;
        $qty = $this->quantityForRow($document, $application);

        if ($qty <= 0) {
            return 0.0;
        }

        return round($perUnit * $qty, 2);
    }

    public function resolvePointsForApprovedDocument(
        ApplicationDocument $document,
        ApplicationEvaluation $evaluation,
    ): float {
        $document->loadMissing(['titleItem.titleGroup', 'application']);

        $item = $document->titleItem;
        if ($item === null || $item->titleGroup === null) {
            return 0.0;
        }

        $application = $document->application;
        if ($application === null) {
            return 0.0;
        }

        $rowMax = $this->maxPointsForRow($document, $application);
        $groupId = (int) $item->process_title_group_id;
        $groupMax = (float) $item->titleGroup->max_score;

        $otherSum = (float) ApplicationEvaluationDocumentScore::query()
            ->where('application_evaluation_id', $evaluation->id)
            ->where('application_document_id', '!=', $document->id)
            ->whereHas('applicationDocument.titleItem', fn ($q) => $q->where('process_title_group_id', $groupId))
            ->sum('pontuacao');

        $remaining = max(0.0, $groupMax - $otherSum);

        return round(min($rowMax, $remaining), 2);
    }
}
