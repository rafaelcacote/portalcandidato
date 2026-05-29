<?php

use App\Http\Controllers\Auth\RegisterCpfAvailabilityController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Legal\PrivacyPolicyController;
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

Route::get('privacidade', [PrivacyPolicyController::class, 'show'])
    ->name('privacy-policy.show');

Route::middleware('throttle:40,1')
    ->get('register/check-cpf', RegisterCpfAvailabilityController::class)
    ->name('register.check-cpf');

Route::get('email/verify/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['web', 'signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardRedirectController::class)->name('dashboard');
    Route::get('processos-seletivos/{selectionProcess}/edital', [SelectionProcessEditalDownloadController::class, 'show'])
        ->name('selection-processes.edital.show');
});

require __DIR__.'/settings.php';
