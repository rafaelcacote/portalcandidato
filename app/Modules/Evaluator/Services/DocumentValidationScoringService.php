<?php

namespace App\Modules\Evaluator\Services;

use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Models\Modules\Evaluator\Models\ApplicationEvaluation;
use App\Modules\Shared\Enums\EvaluationStatus;

class DocumentValidationScoringService
{
    public function __construct(
        private readonly TitleDocumentScoring $titleDocumentScoring,
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

        $this->syncEvaluationTotal($evaluation);

        return $points;
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
