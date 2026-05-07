<?php

namespace App\Modules\Candidate\Services;

use App\Models\Modules\Candidate\Models\Application;
use App\Modules\Shared\Enums\ApplicationStatus;
use Illuminate\Support\Str;

class ApplicationService
{
    public function saveStep(Application $application, int $step, array $payload): Application
    {
        $data = $application->dados_inscricao ?? [];
        $data['step_'.$step] = $payload;

        $application->update([
            'dados_inscricao' => $data,
        ]);

        return $application->refresh();
    }

    public function submit(Application $application): Application
    {
        $application->update([
            'status' => ApplicationStatus::Inscrita->value,
            'finalizada_em' => now(),
            'numero_protocolo' => $application->numero_protocolo ?? $this->generateProtocol(),
        ]);

        return $application->refresh();
    }

    private function generateProtocol(): string
    {
        return 'PS-'.now()->format('Y').'-'.Str::padLeft((string) random_int(1, 999999), 6, '0');
    }
}
