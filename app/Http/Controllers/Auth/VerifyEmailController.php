<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;

class VerifyEmailController extends Controller
{
    /**
     * Confirm the user's e-mail from the signed link (works without a prior session).
     */
    public function __invoke(Request $request, int|string $id, string $hash)
    {
        $user = User::query()->find($id);

        if ($user === null) {
            abort(403, 'Link de verificação inválido.');
        }

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            abort(403, 'Link de verificação inválido.');
        }

        $authenticated = $request->user();

        if ($authenticated === null || (int) $authenticated->getKey() !== (int) $user->getKey()) {
            auth()->login($user);

            if ($request->hasSession()) {
                $request->session()->regenerate();
            }
        }

        if ($user->hasVerifiedEmail()) {
            return app(VerifyEmailResponseContract::class);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return app(VerifyEmailResponseContract::class);
    }
}
