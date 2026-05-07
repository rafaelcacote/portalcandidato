<?php

namespace App\Modules\Evaluator\Services;

use App\Models\Modules\Evaluator\Models\ApplicationEvaluation;
use App\Modules\Shared\Enums\EvaluationStatus;

class EvaluationService
{
    public function markInAnalysis(ApplicationEvaluation $evaluation): void
    {
        $evaluation->update([
            'status' => EvaluationStatus::EmAnalise->value,
        ]);
    }

    public function conclude(ApplicationEvaluation $evaluation, ?string $resultado, ?string $observacoes, float $pontuacaoTotal): void
    {
        $evaluation->update([
            'status' => EvaluationStatus::Concluida->value,
            'resultado' => $resultado,
            'observacoes' => $observacoes,
            'pontuacao_total' => $pontuacaoTotal,
            'concluida_em' => now(),
        ]);
    }
}
