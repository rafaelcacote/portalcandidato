<?php

namespace App\Jobs;

use App\Models\Modules\Candidate\Models\Application;
use App\Modules\Evaluator\Services\ScoringCalculator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessRankingJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly int $selectionProcessId) {}

    /**
     * Execute the job.
     */
    public function handle(ScoringCalculator $scoringCalculator): void
    {
        Application::query()
            ->where('selection_process_id', $this->selectionProcessId)
            ->with('evaluations')
            ->get()
            ->each(function (Application $application) use ($scoringCalculator): void {
                $average = $scoringCalculator->calculateApplicationAverage($application->evaluations);
                $application->update([
                    'dados_inscricao' => array_merge(
                        $application->dados_inscricao ?? [],
                        ['pontuacao_media' => $average],
                    ),
                ]);
            });
    }
}
