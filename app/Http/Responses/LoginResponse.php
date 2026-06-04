<?php

namespace App\Http\Responses;

use App\Modules\Candidate\Services\EnrollmentFinalizeReminderService;
use App\Support\InertiaToast;
use App\Support\RedirectsUsersByRole;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function __construct(
        private readonly EnrollmentFinalizeReminderService $enrollmentFinalizeReminder,
    ) {}

    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if (
            $user !== null
            && $user->hasRole('candidato')
            && $this->enrollmentFinalizeReminder->hasDraftEnrollment($user->id)
        ) {
            InertiaToast::warning(
                $this->enrollmentFinalizeReminder->entryMessage(),
                EnrollmentFinalizeReminderService::TOAST_LIFE_MS,
            );
            $request->session()->put(EnrollmentFinalizeReminderService::SESSION_SHOWN_KEY, true);
        }

        return RedirectsUsersByRole::redirect($request);
    }
}
