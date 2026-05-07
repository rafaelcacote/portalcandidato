<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        foreach (['Admin', 'Candidate', 'Evaluator'] as $module) {
            $routeFile = app_path("Modules/{$module}/routes.php");

            if (is_file($routeFile)) {
                Route::middleware('web')->group($routeFile);
            }
        }
    }
}
