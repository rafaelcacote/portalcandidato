<?php

namespace App\Modules\Evaluator\Services;

use App\Models\Modules\Evaluator\Models\ApplicationEvaluation;
use Illuminate\Support\Collection;

class ScoringCalculator
{
    public function calculateEvaluationTotal(array $scores): float
    {
        return (float) collect($scores)->sum(function (array $score): float {
            return (float) ($score['pontuacao'] ?? 0);
        });
    }

    public function calculateApplicationAverage(Collection $evaluations): float
    {
        if ($evaluations->isEmpty()) {
            return 0.0;
        }

        return round((float) $evaluations->avg('pontuacao_total'), 2);
    }

    public function recalculateForApplication(int $applicationId): float
    {
        $evaluations = ApplicationEvaluation::query()
            ->where('application_id', $applicationId)
            ->whereNotNull('concluida_em')
            ->get(['pontuacao_total']);

        return $this->calculateApplicationAverage($evaluations);
    }
}
