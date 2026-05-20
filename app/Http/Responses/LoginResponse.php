<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if ($user?->hasRole('admin')) {
            return redirect()->intended(route('admin.dashboard'));
        }

        if ($user?->hasRole('avaliador')) {
            return redirect()->intended(route('evaluator.dashboard'));
        }

        if ($user?->hasRole('candidato')) {
            return redirect()->intended(route('candidate.dashboard'));
        }

        return redirect()->intended(config('fortify.home'));
    }
}
