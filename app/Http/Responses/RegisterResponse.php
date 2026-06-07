<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        // Evita redirecionar candidato para URL de admin/avaliador guardada antes do cadastro.
        $request->session()->forget('url.intended');

        return redirect()->route('verification.notice');
    }
}
