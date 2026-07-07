<?php

namespace App\Http\Controllers\Modules\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Candidate\StoreApplicationStepRequest;
use App\Mail\InscricaoConfirmada;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Modules\Candidate\Services\ApplicationService;
use App\Modules\Candidate\Services\EnrollmentFinalizeReminderService;
use App\Modules\Shared\Enums\ApplicationStatus;
use App\Support\InertiaToast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class ApplicationWizardController extends Controller
{
    public function __construct(
        private readonly ApplicationService $applicationService,
        private readonly EnrollmentFinalizeReminderService $enrollmentFinalizeReminder,
    ) {}

    public function start(SelectionProcess $selectionProcess): RedirectResponse
    {
        $user = auth()->user();
        if ($user !== null && $user->hasRole('candidato') && ! $user->candidateProfileIsComplete()) {
            InertiaToast::error('Preencha todos os dados do seu perfil cadastral antes de se inscrever em um processo.');

            return redirect()->route('profile.edit');
        }

        if (! $selectionProcess->inscricaoEstaAberta()) {
            InertiaToast::error('As inscrições para este processo seletivo estão encerradas.');

            return redirect()->route('candidate.processes.index');
        }

        $application = Application::query()->firstOrCreate([
            'selection_process_id' => $selectionProcess->id,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('candidate.applications.show', $application);
    }

    public function storeStep(Application $application, int $step, StoreApplicationStepRequest $request): RedirectResponse
    {
        abort_if($application->user_id !== auth()->id(), 403);
        abort_unless(
            $application->canModifyEnrollment(),
            403,
            'As inscrições para este processo seletivo estão encerradas.',
        );

        $application = $this->applicationService->saveStep(
            $application,
            $step,
            $request->validated()['payload'],
        );

        if ($application->status === ApplicationStatus::Rascunho->value) {
            InertiaToast::warning(
                $this->enrollmentFinalizeReminder->stepSavedMessage($step),
                EnrollmentFinalizeReminderService::TOAST_LIFE_MS,
            );
        } else {
            InertiaToast::success("Etapa {$step} salva.");
        }

        $uiStep = match ($step) {
            3 => 4,
            default => $step,
        };

        return redirect()->to(
            route('candidate.applications.show', $application).'?step='.$uiStep
        );
    }

    public function submit(Application $application): RedirectResponse
    {
        abort_if($application->user_id !== auth()->id(), 403);
        abort_unless(
            $application->canModifyEnrollment(),
            403,
            'As inscrições para este processo seletivo estão encerradas.',
        );

        $application = $this->applicationService->submit($application);
        Mail::to(auth()->user())->queue(new InscricaoConfirmada($application));

        return redirect()
            ->route('candidate.applications.show', $application)
            ->with('success', 'Inscrição finalizada com sucesso.');
    }
}
