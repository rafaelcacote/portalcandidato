<?php

use App\Http\Controllers\SelectionProcessEditalDownloadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

Route::get('/', function (Request $request) {
    if ($request->user()) {
        // Reuse the same role-based redirect logic used after login.
        return app(LoginResponseContract::class)->toResponse($request);
    }

    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('processos-seletivos/{selectionProcess}/edital', [SelectionProcessEditalDownloadController::class, 'show'])
        ->name('selection-processes.edital.show');
});

require __DIR__.'/settings.php';
