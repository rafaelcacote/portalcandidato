<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function adminUserForCandidates(): User
{
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('candidato', 'web');

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('admin');

    return $admin;
}

test('admin can list registered candidates', function () {
    $admin = adminUserForCandidates();

    $candidate = User::factory()->create([
        'name' => 'Candidato Listado',
        'email' => 'candidato.listado@example.com',
        'cpf' => '52998224725',
        'email_verified_at' => now(),
    ]);
    $candidate->assignRole('candidato');

    $this->actingAs($admin)
        ->get(route('admin.candidates.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Candidates/Index')
            ->has('candidates', 1)
            ->where('candidates.0.name', 'Candidato Listado')
            ->where('candidates.0.email', 'candidato.listado@example.com')
            ->where('candidates.0.cpf', '52998224725')
            ->where('candidates.0.applications_count', 0)
            ->where('candidates.0.email_verified', true));
});

test('candidate listing includes candidates without applications', function () {
    $admin = adminUserForCandidates();

    $withApplication = User::factory()->create(['email_verified_at' => now()]);
    $withApplication->assignRole('candidato');

    $withoutApplication = User::factory()->create([
        'name' => 'Sem Inscricao',
        'email_verified_at' => now(),
    ]);
    $withoutApplication->assignRole('candidato');

    $this->actingAs($admin)
        ->get(route('admin.candidates.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Candidates/Index')
            ->has('candidates', 2)
            ->where('candidates', fn ($candidates) => collect($candidates)
                ->contains(fn (array $candidate): bool => $candidate['name'] === 'Sem Inscricao'
                    && $candidate['applications_count'] === 0)));
});

test('candidate cannot access candidates listing', function () {
    Role::findOrCreate('candidato', 'web');

    $candidate = User::factory()->create(['email_verified_at' => now()]);
    $candidate->assignRole('candidato');

    $this->actingAs($candidate)
        ->get(route('admin.candidates.index'))
        ->assertForbidden();
});

test('evaluator cannot access candidates listing', function () {
    Role::findOrCreate('avaliador', 'web');

    $evaluator = User::factory()->create(['email_verified_at' => now()]);
    $evaluator->assignRole('avaliador');

    $this->actingAs($evaluator)
        ->get(route('admin.candidates.index'))
        ->assertForbidden();
});

test('admin can view candidate registration profile', function () {
    $admin = adminUserForCandidates();

    $candidate = User::factory()->completeCandidateProfile()->create([
        'name' => 'Maria Candidata',
        'email' => 'maria.candidata@example.com',
        'email_verified_at' => now(),
    ]);
    $candidate->assignRole('candidato');

    $this->actingAs($admin)
        ->get(route('admin.candidates.show', $candidate))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Candidates/Show')
            ->where('candidate.name', 'Maria Candidata')
            ->where('profile.email', 'maria.candidata@example.com')
            ->where('profile.endereco', $candidate->endereco)
            ->where('profile.telefone', $candidate->telefone));
});

test('admin cannot view non-candidate user profile', function () {
    $admin = adminUserForCandidates();

    $evaluator = User::factory()->create(['email_verified_at' => now()]);
    $evaluator->assignRole('avaliador');

    $this->actingAs($admin)
        ->get(route('admin.candidates.show', $evaluator))
        ->assertNotFound();
});
