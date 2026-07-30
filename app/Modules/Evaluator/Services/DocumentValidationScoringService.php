<?php

namespace App\Modules\Evaluator\Services;

use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Models\Modules\Evaluator\Models\ApplicationEvaluation;
use App\Modules\Shared\Enums\EvaluationStatus;
use Carbon\Carbon;

class DocumentValidationScoringService
{
    public function __construct(
        private readonly TitleDocumentScoring $titleDocumentScoring,
        private readonly TitlePeriodQuantityCalculator $periodQuantityCalculator,
        private readonly ScoringCalculator $scoringCalculator,
        private readonly EvaluationService $evaluationService,
    ) {}

    /**
     * @return float|null Pontos aplicados ao documento de titulação, ou null se não for título.
     */
    public function applyDocumentDecision(
        Application $application,
        ApplicationDocument $applicationDocument,
        string $status,
        int $evaluatorId,
    ): ?float {
        if ($applicationDocument->process_title_item_id === null) {
            return null;
        }

        $applicationDocument->loadMissing('titleItem.titleGroup');

        if ($applicationDocument->titleItem === null) {
            return null;
        }

        $evaluation = ApplicationEvaluation::query()->firstOrCreate(
            [
                'application_id' => $application->id,
                'evaluator_id' => $evaluatorId,
            ],
            [
                'status' => EvaluationStatus::EmAnalise->value,
            ],
        );

        $this->evaluationService->markInAnalysis($evaluation);

        $points = $status === 'aprovado'
            ? $this->titleDocumentScoring->resolvePointsForApprovedDocument($applicationDocument, $evaluation)
            : 0.0;

        $evaluation->documentScores()->updateOrCreate(
            ['application_document_id' => $applicationDocument->id],
            ['pontuacao' => $points],
        );

        if ($status === 'aprovado') {
            $this->recalculateSiblingTitleItemScores($application, $applicationDocument, $evaluation);
        }

        $this->syncEvaluationTotal($evaluation);

        return $points;
    }

    /**
     * Atualiza a quantidade (anos/semestres/unidades) do comprovante.
     * Se o documento já estiver aprovado, recalcula a pontuação.
     *
     * @return float|null Pontos recalculados quando aprovado; null caso contrário.
     */
    public function updateDocumentQuantidade(
        Application $application,
        ApplicationDocument $applicationDocument,
        int $quantidade,
        int $evaluatorId,
    ): ?float {
        abort_if($applicationDocument->process_title_item_id === null, 422);

        $applicationDocument->update([
            'quantidade' => $quantidade,
            'data_inicio' => null,
            'data_fim' => null,
        ]);

        return $this->refreshAfterQuantityChange($application, $applicationDocument, $evaluatorId);
    }

    /**
     * Atualiza o período (datas) do comprovante, calcula a quantidade inteira
     * e recalcula pontuação se já aprovado (incluindo irmãos do mesmo item).
     *
     * @return array{quantidade: int, points: float|null}
     */
    public function updateDocumentPeriod(
        Application $application,
        ApplicationDocument $applicationDocument,
        string $dataInicio,
        string $dataFim,
        int $evaluatorId,
    ): array {
        abort_if($applicationDocument->process_title_item_id === null, 422);

        $applicationDocument->loadMissing('titleItem');
        $item = $applicationDocument->titleItem;
        abort_if($item === null || ! $this->periodQuantityCalculator->usesPeriodDates($item), 422);

        $quantidade = $this->periodQuantityCalculator->quantityFromDates(
            Carbon::parse($dataInicio),
            Carbon::parse($dataFim),
            $this->periodQuantityCalculator->unitIsSemester($item->score_unit),
            $this->periodQuantityCalculator->windowEnd($application),
            $this->periodQuantityCalculator->windowYearsFromPeriodRule($item->period_rule),
        );

        $applicationDocument->update([
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'quantidade' => $quantidade,
        ]);

        $points = $this->refreshAfterQuantityChange($application, $applicationDocument, $evaluatorId);

        return [
            'quantidade' => $quantidade,
            'points' => $points,
        ];
    }

    private function refreshAfterQuantityChange(
        Application $application,
        ApplicationDocument $applicationDocument,
        int $evaluatorId,
    ): ?float {
        $fresh = $applicationDocument->fresh(['titleItem.titleGroup']);

        if ($fresh === null || $fresh->status !== 'aprovado') {
            return null;
        }

        return $this->applyDocumentDecision(
            $application,
            $fresh,
            'aprovado',
            $evaluatorId,
        );
    }

    /**
     * Recalcula pontuações de outros comprovantes aprovados do mesmo item
     * (necessário quando o teto max_quantity é rateado entre eles).
     */
    private function recalculateSiblingTitleItemScores(
        Application $application,
        ApplicationDocument $document,
        ApplicationEvaluation $evaluation,
    ): void {
        $titleItemId = $document->process_title_item_id;
        if ($titleItemId === null) {
            return;
        }

        $siblings = ApplicationDocument::query()
            ->where('application_id', $application->id)
            ->where('process_title_item_id', $titleItemId)
            ->where('status', 'aprovado')
            ->where('id', '!=', $document->id)
            ->with(['titleItem.titleGroup', 'application'])
            ->orderBy('id')
            ->get();

        foreach ($siblings as $sibling) {
            $points = $this->titleDocumentScoring->resolvePointsForApprovedDocument($sibling, $evaluation);
            $evaluation->documentScores()->updateOrCreate(
                ['application_document_id' => $sibling->id],
                ['pontuacao' => $points],
            );
        }
    }

    private function syncEvaluationTotal(ApplicationEvaluation $evaluation): void
    {
        $evaluation->load(['scores', 'documentScores']);

        $criteriaScores = $evaluation->scores
            ->map(fn ($score): array => ['pontuacao' => $score->pontuacao])
            ->all();

        $documentScores = $evaluation->documentScores
            ->map(fn ($score): array => ['pontuacao' => $score->pontuacao])
            ->all();

        $total = $this->scoringCalculator->calculateEvaluationTotal($criteriaScores, $documentScores);

        $evaluation->update(['pontuacao_total' => $total]);
    }
}
