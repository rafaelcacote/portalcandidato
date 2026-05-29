<?php

namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PrivacyPolicyController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Legal/PrivacyPolicy', [
            'dataController' => config('lgpd.data_controller'),
            'contactEmail' => config('lgpd.contact_email'),
        ]);
    }
}
