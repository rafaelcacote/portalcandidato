<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

test('generic dashboard route redirects candidates to candidate dashboard', function (): void {
    Role::findOrCreate('candidato', 'web');

    $user = User::factory()->create();
    $user->assignRole('candidato');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('candidate.dashboard'));
});

test('generic dashboard route preserves query string when redirecting', function (): void {
    Role::findOrCreate('candidato', 'web');

    $user = User::factory()->create();
    $user->assignRole('candidato');

    $this->actingAs($user)
        ->get(route('dashboard', ['verified' => 1]))
        ->assertRedirect(route('candidate.dashboard').'?verified=1');
});
