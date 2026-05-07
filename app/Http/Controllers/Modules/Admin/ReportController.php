<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modules\Admin\Models\SelectionProcess;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Reports/Index', [
            'processes' => SelectionProcess::query()->select('id', 'titulo', 'status')->orderBy('titulo')->get(),
        ]);
    }
}
