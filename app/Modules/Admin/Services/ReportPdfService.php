<?php

namespace App\Modules\Admin\Services;

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use App\Modules\Candidate\Services\ApplicationPdfService;
use App\Modules\Candidate\Support\ResearchLineCatalog;
use App\Modules\Shared\Enums\ApplicationStatus;
use App\Rules\Cpf;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class ReportPdfService
{
    /**
     * @param  array{
     *     search?: string,
     *     pcd?: string,
     *     vinculo?: string,
     *     linha_pesquisa?: string,
     *     orientador?: string,
     *     status?: string
     * }  $filters
     */
    public function inlineEnrolledCandidatesList(
        SelectionProcess $selectionProcess,
        array $filters = [],
    ): Response {
        $candidates = $this->enrolledCandidates($selectionProcess, $filters);
        $appliedFilters = $this->activeFilterLabels($selectionProcess, $filters);

        return Pdf::loadView('pdfs.lista-candidatos', [
            'process' => $selectionProcess,
            'candidates' => $candidates,
            'appliedFilters' => $appliedFilters,
            'institution' => config('lgpd.data_controller'),
            'generatedAt' => now()->timezone(config('app.timezone'))->format('d/m/Y'),
            'logoDataUri' => ApplicationPdfService::proensLogoDataUri(),
        ])
            ->setPaper('a4', 'portrait')
            ->stream($this->filename($selectionProcess));
    }

    /**
     * @param  array{
     *     search?: string,
     *     pcd?: string,
     *     vinculo?: string,
     *     linha_pesquisa?: string,
     *     orientador?: string,
     *     status?: string
     * }  $filters
     * @return list<string>
     */
    public function activeFilterLabels(SelectionProcess $selectionProcess, array $filters = []): array
    {
        $labels = [];
        $search = trim((string) ($filters['search'] ?? ''));
        $pcd = $this->normalizedChoice($filters['pcd'] ?? null);
        $vinculo = $this->normalizedChoice($filters['vinculo'] ?? null);
        $linhaPesquisa = trim((string) ($filters['linha_pesquisa'] ?? ''));
        $orientador = trim((string) ($filters['orientador'] ?? ''));
        $status = $this->normalizedChoice($filters['status'] ?? null);

        if ($search !== '') {
            $labels[] = 'Busca: '.$search;
        }

        if ($pcd === 'sim') {
            $labels[] = 'PcD: candidatos que concorrem às vagas PcD';
        } elseif ($pcd === 'nao') {
            $labels[] = 'PcD: candidatos que não concorrem às vagas PcD';
        }

        if ($vinculo === 'sem_vinculo') {
            $labels[] = 'Vínculo empregatício: sem vínculo';
        } elseif ($vinculo === 'com_vinculo') {
            $labels[] = 'Vínculo empregatício: com vínculo';
        }

        if ($linhaPesquisa !== '') {
            $lineLabel = ResearchLineCatalog::lineLabel($linhaPesquisa, $selectionProcess->id)
                ?? $linhaPesquisa;
            $labels[] = 'Linha de pesquisa: '.$this->shortResearchLineLabel($lineLabel);
        }

        if ($orientador !== '') {
            $labels[] = 'Orientador: '.$orientador;
        }

        if ($status !== 'all') {
            $labels[] = 'Status da inscrição: '.$this->statusLabel($status);
        }

        return $labels;
    }

    /**
     * @param  array{
     *     search?: string,
     *     pcd?: string,
     *     vinculo?: string,
     *     linha_pesquisa?: string,
     *     orientador?: string,
     *     status?: string
     * }  $filters
     * @return Collection<int, array{numero_protocolo: string|null, nome_completo: string|null, linha_pesquisa_label: string|null, cpf_mascarado: string|null}>
     */
    public function enrolledCandidates(SelectionProcess $selectionProcess, array $filters = []): Collection
    {
        return $this->enrolledApplicationsQuery($selectionProcess, $filters)
            ->get()
            ->map(fn (Application $application): array => $this->mapApplicationForReport($application));
    }

    /**
     * @return array{
     *     numero_protocolo: string|null,
     *     nome_completo: string|null,
     *     linha_pesquisa_label: string|null,
     *     cpf_mascarado: string|null
     * }
     */
    public function mapApplicationForReport(Application $application): array
    {
        $step3 = $application->dados_inscricao['step_3'] ?? null;
        $researchLineSummary = ResearchLineCatalog::summaryFromStepData(
            is_array($step3) ? $step3 : null,
            $application->selection_process_id,
        );

        return [
            'numero_protocolo' => $application->numero_protocolo,
            'nome_completo' => $application->user?->name,
            'linha_pesquisa_label' => $this->shortResearchLineLabel(
                $researchLineSummary['linha_pesquisa_label'] ?? null,
            ),
            'cpf_mascarado' => Cpf::maskForDisplay($application->user?->cpf),
        ];
    }

    private function shortResearchLineLabel(?string $label): ?string
    {
        if ($label === null || trim($label) === '') {
            return null;
        }

        return trim(explode(' - ', $label, 2)[0]);
    }

    /**
     * @param  array{
     *     search?: string,
     *     pcd?: string,
     *     vinculo?: string,
     *     linha_pesquisa?: string,
     *     orientador?: string,
     *     status?: string
     * }  $filters
     * @return Builder<Application>
     */
    public function enrolledApplicationsQuery(SelectionProcess $selectionProcess, array $filters = [])
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $pcd = $this->normalizedChoice($filters['pcd'] ?? null);
        $vinculo = $this->normalizedChoice($filters['vinculo'] ?? null);
        $linhaPesquisa = trim((string) ($filters['linha_pesquisa'] ?? ''));
        $orientador = trim((string) ($filters['orientador'] ?? ''));
        $status = $this->normalizedChoice($filters['status'] ?? null);

        return Application::query()
            ->where('selection_process_id', $selectionProcess->id)
            ->whereNotNull('finalizada_em')
            ->whereNotNull('numero_protocolo')
            ->where('status', '!=', ApplicationStatus::Rascunho->value)
            ->with('user:id,name,cpf')
            ->when(
                $search !== '',
                fn (Builder $query) => $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery
                        ->where('numero_protocolo', 'like', "%{$search}%")
                        ->orWhereHas(
                            'user',
                            fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$search}%"),
                        );
                }),
            )
            ->when(
                $pcd === 'sim',
                fn (Builder $query) => $query->where('dados_inscricao->step_1->concorre_vagas_pcd', true),
            )
            ->when(
                $pcd === 'nao',
                fn (Builder $query) => $query->where('dados_inscricao->step_1->concorre_vagas_pcd', false),
            )
            ->when(
                $vinculo === 'sem_vinculo',
                fn (Builder $query) => $query->where('dados_inscricao->step_2->concorre_vagas_sem_vinculo', true),
            )
            ->when(
                $vinculo === 'com_vinculo',
                fn (Builder $query) => $query->where('dados_inscricao->step_2->concorre_vagas_sem_vinculo', false),
            )
            ->when(
                $linhaPesquisa !== '',
                fn (Builder $query) => $query->where('dados_inscricao->step_3->linha_pesquisa', $linhaPesquisa),
            )
            ->when(
                $orientador !== '',
                fn (Builder $query) => $query->where('dados_inscricao->step_3->orientador', $orientador),
            )
            ->when(
                $status !== 'all',
                fn (Builder $query) => $query->where('status', $status),
            )
            ->orderBy(
                User::query()
                    ->select('name')
                    ->whereColumn('users.id', 'applications.user_id')
                    ->limit(1),
            );
    }

    private function normalizedChoice(mixed $value): string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? 'all' : $normalized;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            ApplicationStatus::Inscrita->value => 'Inscrita',
            ApplicationStatus::EmAnalise->value => 'Em análise',
            ApplicationStatus::Pendencia->value => 'Pendência',
            ApplicationStatus::Aprovada->value => 'Aprovada',
            ApplicationStatus::Reprovada->value => 'Reprovada',
            ApplicationStatus::Cancelada->value => 'Cancelada',
            default => $status,
        };
    }

    private function filename(SelectionProcess $selectionProcess): string
    {
        return str('lista-candidatos-'.$selectionProcess->titulo)->slug().'.pdf';
    }
}
