<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('database seed creates only the admin user', function (): void {
    $this->seed(DatabaseSeeder::class);

    $admin = User::query()->where('email', 'admin@portalcandidato.local')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->hasRole('admin'))->toBeTrue()
        ->and(User::query()->count())->toBe(1)
        ->and(User::query()->where('email', 'candidato@portalcandidato.local')->exists())->toBeFalse()
        ->and(SelectionProcess::query()->where('titulo', 'Processo Seletivo Demo — Mestrado')->exists())->toBeFalse();
});
