<?php

namespace App\Modules\Admin\Services;

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use App\Modules\Candidate\Services\ApplicationPdfService;
use App\Modules\Shared\Enums\ApplicationStatus;
use App\Modules\Shared\Enums\EvaluationStatus;
use App\Rules\Cpf;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class CandidateContactsReportService
{
    /**
     * @param  array{selection_process_id?: int|null}  $filters
     */
    public function inlineContactsList(array $filters = []): Response
    {
        $candidates = $this->candidates($filters);
        $appliedFilters = $this->activeFilterLabels($filters);
        $processTitle = $this->processTitle($filters['selection_process_id'] ?? null);

        return Pdf::loadView('pdfs.lista-candidatos-contatos', [
            'processTitle' => $processTitle,
            'candidates' => $candidates,
            'appliedFilters' => $appliedFilters,
            'institution' => config('lgpd.data_controller'),
            'generatedAt' => now()->timezone(config('app.timezone'))->format('d/m/Y'),
            'logoDataUri' => ApplicationPdfService::proensLogoDataUri(),
        ])
            ->setPaper('a4', 'landscape')
            ->stream($this->filename($processTitle));
    }

    /**
     * @param  array{selection_process_id?: int|null}  $filters
     * @return list<string>
     */
    public function activeFilterLabels(array $filters = []): array
    {
        $labels = [];
        $selectionProcessId = $filters['selection_process_id'] ?? null;

        if ($selectionProcessId !== null) {
            $processTitle = SelectionProcess::query()
                ->whereKey($selectionProcessId)
                ->value('titulo');

            if (is_string($processTitle) && $processTitle !== '') {
                $labels[] = 'Processo seletivo: '.$processTitle;
            }
        }

        return $labels;
    }

    /**
     * @param  array{selection_process_id?: int|null}  $filters
     * @return Collection<int, array{id: int, numero_protocolo: string|null, nome_completo: string|null, cpf_mascarado: string|null, email: string|null}>
     */
    public function candidates(array $filters = []): Collection
    {
        return $this->query($filters)
            ->get()
            ->map(fn (Application $application): array => $this->mapApplication($application));
    }

    /**
     * @param  array{selection_process_id?: int|null}  $filters
     * @return Builder<Application>
     */
    public function query(array $filters = []): Builder
    {
        $selectionProcessId = $filters['selection_process_id'] ?? null;

        return Application::query()
            ->whereNotNull('finalizada_em')
            ->whereNotNull('numero_protocolo')
            ->where('status', '!=', ApplicationStatus::Rascunho->value)
            ->whereHas(
                'evaluations',
                fn (Builder $query) => $query->where('status', EvaluationStatus::Concluida->value),
            )
            ->with([
                'user:id,name,cpf,email',
                'selectionProcess:id,titulo',
            ])
            ->when(
                $selectionProcessId !== null,
                fn (Builder $query) => $query->where('selection_process_id', $selectionProcessId),
            )
            ->orderBy(
                User::query()
                    ->select('name')
                    ->whereColumn('users.id', 'applications.user_id')
                    ->limit(1),
            )
            ->orderBy('applications.id');
    }

    /**
     * @return array{
     *     id: int,
     *     numero_protocolo: string|null,
     *     nome_completo: string|null,
     *     cpf_mascarado: string|null,
     *     email: string|null
     * }
     */
    public function mapApplication(Application $application): array
    {
        return [
            'id' => $application->id,
            'numero_protocolo' => $application->numero_protocolo,
            'nome_completo' => $application->user?->name,
            'cpf_mascarado' => Cpf::maskForDisplay($application->user?->cpf),
            'email' => $application->user?->email,
        ];
    }

    /**
     * @return array{processes: list<array{value: int, label: string}>}
     */
    public function filterOptions(): array
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
        return str('lista-candidatos-contatos-'.$processTitle)->slug().'.pdf';
    }
}
