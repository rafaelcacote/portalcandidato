<?php

namespace App\Modules\Admin\Services;

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use App\Modules\Shared\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * TEMPORÁRIO — remover após preencher linhas de pesquisa dos candidatos legados.
 */
class MissingResearchLineBackfillService
{
    public function hasResearchLine(Application $application): bool
    {
        $step3 = $application->dados_inscricao['step_3'] ?? null;

        if (! is_array($step3)) {
            return false;
        }

        return trim((string) ($step3['linha_pesquisa'] ?? '')) !== '';
    }

    /**
     * @return Builder<Application>
     */
    public function enrolledApplicationsQuery(?int $selectionProcessId = null): Builder
    {
        return Application::query()
            ->when(
                $selectionProcessId !== null,
                fn (Builder $query) => $query->where('selection_process_id', $selectionProcessId),
            )
            ->whereNotNull('finalizada_em')
            ->whereNotNull('numero_protocolo')
            ->where('status', '!=', ApplicationStatus::Rascunho->value)
            ->with('user:id,name')
            ->orderBy(
                User::query()
                    ->select('name')
                    ->whereColumn('users.id', 'applications.user_id')
                    ->limit(1),
            );
    }

    /**
     * @return Collection<int, Application>
     */
    public function applicationsMissingResearchLine(?int $selectionProcessId = null): Collection
    {
        return $this->enrolledApplicationsQuery($selectionProcessId)
            ->get()
            ->filter(fn (Application $application): bool => ! $this->hasResearchLine($application))
            ->values();
    }

    public function countMissingResearchLine(?int $selectionProcessId = null): int
    {
        return $this->applicationsMissingResearchLine($selectionProcessId)->count();
    }

    /**
     * @return Collection<int, array{
     *     id: int,
     *     titulo: string,
     *     status: string,
     *     missing_research_lines_count: int
     * }>
     */
    public function processesWithMissingCounts(): Collection
    {
        return SelectionProcess::query()
            ->select('id', 'titulo', 'status')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (SelectionProcess $process): array {
                return [
                    'id' => $process->id,
                    'titulo' => $process->titulo,
                    'status' => $process->status,
                    'missing_research_lines_count' => $this->countMissingResearchLine($process->id),
                ];
            })
            ->filter(fn (array $process): bool => $process['missing_research_lines_count'] > 0)
            ->values();
    }

    /**
     * @return array{
     *     id: int,
     *     numero_protocolo: string|null,
     *     nome_completo: string|null
     * }
     */
    public function serializeApplication(Application $application): array
    {
        return [
            'id' => $application->id,
            'numero_protocolo' => $application->numero_protocolo,
            'nome_completo' => $application->user?->name,
        ];
    }

    public function updateResearchLine(Application $application, string $linhaPesquisa, string $orientador): Application
    {
        $data = $application->dados_inscricao ?? [];
        $data['step_3'] = [
            'linha_pesquisa' => $linhaPesquisa,
            'orientador' => $orientador,
        ];

        $application->update([
            'dados_inscricao' => $data,
        ]);

        return $application->refresh();
    }
}
