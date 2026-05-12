<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\User;
use Database\Seeders\CandidateAndSelectionProcessSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('candidate and selection process demo seed creates expected records', function (): void {
    $this->seed(RolesSeeder::class);
    $this->seed(CandidateAndSelectionProcessSeeder::class);

    $candidate = User::query()->where('email', 'candidato@portalcandidato.local')->first();

    expect($candidate)->not->toBeNull()
        ->and($candidate->hasRole('candidato'))->toBeTrue();

    $process = SelectionProcess::query()->where('titulo', 'Processo Seletivo Demo — Mestrado')->first();

    expect($process)->not->toBeNull()
        ->and($process->status)->toBe('ativo')
        ->and($process->tipo_programa?->value)->toBe('mestrado');
});
