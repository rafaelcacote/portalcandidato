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

    public function applyDocumentDecision(
        Application $application,
        ApplicationDocument $applicationDocument,
        string $status,
        int $evaluatorId,
    ): void {
        if ($applicationDocument->process_title_item_id === null) {
            return;
        }

        $applicationDocument->loadMissing('titleItem.titleGroup');

        if ($applicationDocument->titleItem === null) {
            return;
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
