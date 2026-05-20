<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RedirectsUsersByRole
{
    public static function destinationFor(?User $user): string
    {
        if ($user?->hasRole('admin')) {
            return route('admin.dashboard');
        }

        if ($user?->hasRole('avaliador')) {
            return route('evaluator.dashboard');
        }

        if ($user?->hasRole('candidato')) {
            return route('candidate.dashboard');
        }

        return route('login');
    }

    /**
     * @param  array<string, string>  $query
     */
    public static function redirect(Request $request, bool $intended = true, array $query = []): RedirectResponse
    {
        $url = static::destinationFor($request->user());

        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($query);
        }

        return $intended
            ? redirect()->intended($url)
            : redirect($url);
    }

    public static function redirectPreservingQuery(Request $request): RedirectResponse
    {
        $url = static::destinationFor($request->user());

        if ($request->getQueryString()) {
            $url .= (str_contains($url, '?') ? '&' : '?').$request->getQueryString();
        }

        return redirect($url);
    }
}
