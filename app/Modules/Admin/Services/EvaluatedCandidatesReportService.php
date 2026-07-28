<?php

namespace App\Modules\Admin\Services;

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use App\Modules\Candidate\Services\ApplicationPdfService;
use App\Modules\Candidate\Support\ResearchLineCatalog;
use App\Modules\Shared\Enums\EvaluationStatus;
use App\Rules\Cpf;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class EvaluatedCandidatesReportService
{
    /**
     * @param  array{
     *     selection_process_id?: int|null,
     *     linha_pesquisa?: string
     * }  $filters
     */
    public function inlineEvaluatedCandidatesList(array $filters = []): Response
    {
        $candidates = $this->evaluatedCandidates($filters);
        $appliedFilters = $this->activeFilterLabels($filters);
        $processTitle = $this->processTitle($filters['selection_process_id'] ?? null);

        return Pdf::loadView('pdfs.lista-candidatos-avaliados', [
            'processTitle' => $processTitle,
            'candidates' => $candidates,
            'appliedFilters' => $appliedFilters,
            'institution' => config('lgpd.data_controller'),
            'generatedAt' => now()->timezone(config('app.timezone'))->format('d/m/Y'),
            'logoDataUri' => ApplicationPdfService::proensLogoDataUri(),
        ])
            ->setPaper('a4', 'portrait')
            ->stream($this->filename($processTitle));
    }

    /**
     * @param  array{
     *     selection_process_id?: int|null,
     *     linha_pesquisa?: string
     * }  $filters
     * @return list<string>
     */
    public function activeFilterLabels(array $filters = []): array
    {
        $labels = [];
        $selectionProcessId = $filters['selection_process_id'] ?? null;
        $linhaPesquisa = trim((string) ($filters['linha_pesquisa'] ?? ''));

        if ($selectionProcessId !== null) {
            $processTitle = SelectionProcess::query()
                ->whereKey($selectionProcessId)
                ->value('titulo');

            if (is_string($processTitle) && $processTitle !== '') {
                $labels[] = 'Processo seletivo: '.$processTitle;
            }
        }

        if ($linhaPesquisa !== '') {
            $lineLabel = ResearchLineCatalog::lineLabel($linhaPesquisa, $selectionProcessId)
                ?? $linhaPesquisa;
            $labels[] = 'Linha de pesquisa: '.$this->shortResearchLineLabel($lineLabel);
        }

        return $labels;
    }

    /**
     * @param  array{
     *     selection_process_id?: int|null,
     *     linha_pesquisa?: string
     * }  $filters
     * @return Collection<int, array{id: int, numero_protocolo: string|null, nome_completo: string|null, cpf_mascarado: string|null, nota: float}>
     */
    public function evaluatedCandidates(array $filters = []): Collection
    {
        return $this->query($filters)
            ->get()
            ->map(fn (Application $application): array => $this->mapApplication($application));
    }

    /**
     * @param  array{
     *     selection_process_id?: int|null,
     *     linha_pesquisa?: string
     * }  $filters
     * @return Builder<Application>
     */
    public function query(array $filters = []): Builder
    {
        $selectionProcessId = $filters['selection_process_id'] ?? null;
        $linhaPesquisa = trim((string) ($filters['linha_pesquisa'] ?? ''));

        return Application::query()
            ->whereNotNull('finalizada_em')
            ->whereNotNull('numero_protocolo')
            ->whereHas(
                'evaluations',
                fn (Builder $query) => $query->where('status', EvaluationStatus::Concluida->value),
            )
            ->withAvg(
                [
                    'evaluations as nota' => fn (Builder $query) => $query
                        ->where('status', EvaluationStatus::Concluida->value),
                ],
                'pontuacao_total',
            )
            ->with([
                'user:id,name,cpf',
                'selectionProcess:id,titulo',
            ])
            ->when(
                $selectionProcessId !== null,
                fn (Builder $query) => $query->where('selection_process_id', $selectionProcessId),
            )
            ->when(
                $linhaPesquisa !== '',
                fn (Builder $query) => $query->where('dados_inscricao->step_3->linha_pesquisa', $linhaPesquisa),
            )
            ->orderBy(
                User::query()
                    ->select('name')
                    ->whereColumn('users.id', 'applications.user_id')
                    ->limit(1),
            );
    }

    /**
     * @return array{
     *     id: int,
     *     numero_protocolo: string|null,
     *     nome_completo: string|null,
     *     cpf_mascarado: string|null,
     *     nota: float
     * }
     */
    public function mapApplication(Application $application): array
    {
        return [
            'id' => $application->id,
            'numero_protocolo' => $application->numero_protocolo,
            'nome_completo' => $application->user?->name,
            'cpf_mascarado' => Cpf::maskForDisplay($application->user?->cpf),
            'nota' => round((float) ($application->nota ?? 0), 2),
        ];
    }

    /**
     * @return array{
     *     processes: list<array{value: int, label: string}>,
     *     researchLines: list<array{value: string, label: string}>
     * }
     */
    public function filterOptions(?int $selectionProcessId = null): array
    {
        return [
            'processes' => SelectionProcess::query()
                ->select('id', 'titulo')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (SelectionProcess $process): array => [
                    'value' => $process->id,
                    'label' => $process->titulo,
                ])
                ->values()
                ->all(),
            'researchLines' => collect(ResearchLineCatalog::lines($selectionProcessId))
                ->map(fn (string $label, string $value): array => [
                    'value' => $value,
                    'label' => $this->shortResearchLineLabel($label) ?? $label,
                ])
                ->values()
                ->all(),
        ];
    }

    private function processTitle(?int $selectionProcessId): string
    {
        if ($selectionProcessId === null) {
            return 'Todos os processos';
        }

        $title = SelectionProcess::query()
            ->whereKey($selectionProcessId)
            ->value('titulo');

        return is_string($title) && $title !== '' ? $title : 'Processo seletivo';
    }

    private function filename(string $processTitle): string
    {
        return str('lista-candidatos-avaliados-'.$processTitle)->slug().'.pdf';
    }

    private function shortResearchLineLabel(?string $label): ?string
    {
        if ($label === null || trim($label) === '') {
            return null;
        }

        return trim(explode(' - ', $label, 2)[0]);
    }
}
