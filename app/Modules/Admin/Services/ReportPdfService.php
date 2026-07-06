<?php

namespace App\Modules\Admin\Services;

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Modules\Candidate\Services\ApplicationPdfService;
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
     * @return Collection<int, array{numero_protocolo: string|null, nome_completo: string|null, cpf_mascarado: string|null}>
     */
    public function enrolledCandidates(SelectionProcess $selectionProcess): Collection
    {
        return $this->enrolledApplicationsQuery($selectionProcess)
            ->get()
            ->map(fn (Application $application): array => [
                'numero_protocolo' => $application->numero_protocolo,
                'nome_completo' => $application->user?->name,
                'cpf_mascarado' => Cpf::maskForDisplay($application->user?->cpf),
            ]);
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
            ->orderBy('numero_protocolo');
    }

    private function filename(SelectionProcess $selectionProcess): string
    {
        return str('lista-candidatos-'.$selectionProcess->titulo)->slug().'.pdf';
    }
}
