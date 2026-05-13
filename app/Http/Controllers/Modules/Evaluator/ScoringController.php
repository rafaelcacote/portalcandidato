<?php

namespace App\Http\Controllers\Modules\Evaluator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Evaluator\StoreCandidateScoreRequest;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Evaluator\Models\ApplicationEvaluation;
use App\Modules\Evaluator\Services\EvaluationService;
use App\Modules\Evaluator\Services\ScoringCalculator;
use Illuminate\Http\RedirectResponse;

class ScoringController extends Controller
{
    public function __construct(
        private readonly ScoringCalculator $scoringCalculator,
        private readonly EvaluationService $evaluationService,
    ) {}

    public function store(Application $application, StoreCandidateScoreRequest $request): RedirectResponse
    {
        $evaluation = ApplicationEvaluation::query()->firstOrCreate(
            [
                'application_id' => $application->id,
                'evaluator_id' => auth()->id(),
            ],
            [
                'status' => 'em_analise',
            ],
        );

        $scores = $request->validated('scores');
        $documentScores = $request->validated('document_scores');

        $evaluation->scores()->delete();
        $evaluation->scores()->createMany($scores);

        $evaluation->documentScores()->delete();
        $evaluation->documentScores()->createMany($documentScores);

        $total = $this->scoringCalculator->calculateEvaluationTotal($scores, $documentScores);
        $this->evaluationService->conclude(
            $evaluation,
            $request->input('resultado'),
            $request->input('observacoes'),
            $total,
        );

        return back()->with('success', 'Pontuação salva com sucesso.');
    }
}
