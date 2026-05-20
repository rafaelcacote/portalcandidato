<?php

namespace App\Http\Controllers;

use App\Support\RedirectsUsersByRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if ($request->user() === null) {
            return redirect()->route('login');
        }

        return RedirectsUsersByRole::redirectPreservingQuery($request);
    }
}
