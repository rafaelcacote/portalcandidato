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
    public function inlineEnrolledCandidatesList(SelectionProcess $selectionProcess): Response
    {
        $candidates = $this->enrolledCandidates($selectionProcess);

        return Pdf::loadView('pdfs.lista-candidatos', [
            'process' => $selectionProcess,
            'candidates' => $candidates,
            'institution' => config('lgpd.data_controller'),
            'generatedAt' => now()->timezone(config('app.timezone'))->format('d/m/Y'),
            'logoDataUri' => ApplicationPdfService::proensLogoDataUri(),
        ])
            ->setPaper('a4', 'portrait')
            ->stream($this->filename($selectionProcess));
    }

    /**
     * @return Collection<int, array{numero_protocolo: string|null, nome_completo: string|null, linha_pesquisa_label: string|null, cpf_mascarado: string|null}>
     */
    public function enrolledCandidates(SelectionProcess $selectionProcess): Collection
    {
        return $this->enrolledApplicationsQuery($selectionProcess)
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
     * @return Builder<Application>
     */
    public function enrolledApplicationsQuery(SelectionProcess $selectionProcess)
    {
        return Application::query()
            ->where('selection_process_id', $selectionProcess->id)
            ->whereNotNull('finalizada_em')
            ->whereNotNull('numero_protocolo')
            ->where('status', '!=', ApplicationStatus::Rascunho->value)
            ->with('user:id,name,cpf')
            ->orderBy(
                User::query()
                    ->select('name')
                    ->whereColumn('users.id', 'applications.user_id')
                    ->limit(1),
            );
    }

    private function filename(SelectionProcess $selectionProcess): string
    {
        return str('lista-candidatos-'.$selectionProcess->titulo)->slug().'.pdf';
    }
}
