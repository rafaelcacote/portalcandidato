<?php

namespace App\Modules\Candidate\Services;

use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Modules\Candidate\Enums\CandidaturaSpecialDocumentKind;
use App\Modules\Candidate\Support\ResearchLineCatalog;
use App\Modules\Shared\Enums\ApplicationStatus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplicationService
{
    public function saveStep(Application $application, int $step, array $payload): Application
    {
        if ($step === 1 && array_key_exists('concorre_vagas_pcd', $payload) && $payload['concorre_vagas_pcd'] === false) {
            $this->deletePcdDocuments($application);
        }

        $data = $application->dados_inscricao ?? [];
        $data['step_'.$step] = $payload;

        $application->update([
            'dados_inscricao' => $data,
        ]);

        return $application->refresh();
    }

    public function submit(Application $application): Application
    {
        $this->assertSubmitRequirements($application);

        $application->update([
            'status' => ApplicationStatus::Inscrita->value,
            'finalizada_em' => now(),
            'numero_protocolo' => $application->numero_protocolo ?? $this->generateProtocol(),
        ]);

        return $application->refresh();
    }

    private function assertSubmitRequirements(Application $application): void
    {
        $data = $application->dados_inscricao ?? [];
        $this->assertEmploymentStepPresent($data);
        $this->assertAcademicFieldsPresent($application, $data);

        $declaraPcd = ($data['step_1']['concorre_vagas_pcd'] ?? false) === true;
        if (! $declaraPcd) {
            return;
        }

        $application->loadMissing('documents');

        $presentKinds = $application->documents
            ->where('status', '!=', 'recusado')
            ->pluck('candidatura_document_kind')
            ->filter()
            ->all();

        foreach (CandidaturaSpecialDocumentKind::cases() as $case) {
            if (! in_array($case->value, $presentKinds, true)) {
                throw ValidationException::withMessages([
                    'submit' => 'Envie a declaração PcD e o laudo médico ou carteira PcD antes de finalizar a inscrição.',
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertEmploymentStepPresent(array $data): void
    {
        if (! array_key_exists('step_2', $data)) {
            throw ValidationException::withMessages([
                'submit' => 'Responda a pergunta sobre vínculo empregatício antes de finalizar a inscrição.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertAcademicFieldsPresent(Application $application, array $data): void
    {
        $step3 = is_array($data['step_3'] ?? null) ? $data['step_3'] : [];
        $linhaPesquisa = trim((string) ($step3['linha_pesquisa'] ?? ''));
        $orientador = trim((string) ($step3['orientador'] ?? ''));

        if ($linhaPesquisa === '' || $orientador === '') {
            throw ValidationException::withMessages([
                'submit' => 'Selecione a linha de pesquisa e o orientador antes de finalizar a inscrição.',
            ]);
        }

        if (! ResearchLineCatalog::isValidAdvisor($linhaPesquisa, $orientador, $application->selection_process_id)) {
            throw ValidationException::withMessages([
                'submit' => 'A linha de pesquisa e o orientador informados não são válidos. Revise a etapa correspondente.',
            ]);
        }
    }

    private function deletePcdDocuments(Application $application): void
    {
        ApplicationDocument::query()
            ->where('application_id', $application->id)
            ->whereIn('candidatura_document_kind', CandidaturaSpecialDocumentKind::values())
            ->get()
            ->each(function (ApplicationDocument $document): void {
                if ($document->caminho !== '') {
                    Storage::delete($document->caminho);
                }
                $document->delete();
            });
    }

    private function generateProtocol(): string
    {
        return 'PS-'.now()->format('Y').'-'.Str::padLeft((string) random_int(1, 999999), 6, '0');
    }
}
