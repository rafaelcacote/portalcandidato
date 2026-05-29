<?php

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use App\Support\BrazilianStates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::query()->firstOrCreate([
        'name' => 'candidato',
        'guard_name' => 'web',
    ]);
});

/**
 * @return array<string, mixed>
 */
function validCandidateProfileUpdatePayload(string $email): array
{
    return [
        'name' => 'Maria Silva Atualizada',
        'email' => $email,
        'data_nascimento' => '1992-04-10',
        'identidade' => '987654321',
        'orgao_emissor' => 'SSP',
        'identidade_uf' => 'RJ',
        'identidade_data_emissao' => '2016-08-01',
        'naturalidade' => 'Rio de Janeiro',
        'nacionalidade' => 'Brasileira',
        'sexo' => 'feminino',
        'endereco' => 'Av. Brasil',
        'endereco_numero' => '500',
        'bairro' => 'Copacabana',
        'cep' => '22041080',
        'cidade' => 'Rio de Janeiro',
        'endereco_uf' => 'RJ',
        'pais' => 'Brasil',
        'telefone' => '(21) 99999-8888',
        'telefone_fixo' => '(21) 3333-4444',
    ];
}

test('candidate profile page exposes profile data and ufs', function () {
    $user = User::factory()->completeCandidateProfile()->create();
    $user->assignRole('candidato');

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Profile')
            ->where('isCandidate', true)
            ->has('profile')
            ->where('ufs', BrazilianStates::abbreviations())
            ->where('profile.name', $user->name)
            ->where('profile.cpf', $user->cpf),
        );
});

test('candidate can update personal profile information', function () {
    Storage::fake('public');

    $user = User::factory()->completeCandidateProfile()->create([
        'email' => 'candidato-perfil@example.com',
        'sexo' => 'feminino',
    ]);
    $user->assignRole('candidato');

    $payload = validCandidateProfileUpdatePayload('novo-email-perfil@example.com');

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Maria Silva Atualizada');
    expect($user->email)->toBe('novo-email-perfil@example.com');
    expect($user->telefone)->toBe('(21) 99999-8888');
    expect($user->cidade)->toBe('Rio de Janeiro');
    expect($user->cep)->toBe('22041080');
    expect($user->email_verified_at)->toBeNull();
});

test('candidate profile update can return to enrollment page when stay_on_page is set', function () {
    $user = User::factory()->completeCandidateProfile()->create();
    $user->assignRole('candidato');

    $process = SelectionProcess::query()->create([
        'titulo' => 'Processo teste',
        'descricao' => 'Descrição',
        'status' => 'ativo',
    ]);

    $application = Application::query()->create([
        'user_id' => $user->id,
        'selection_process_id' => $process->id,
        'status' => 'rascunho',
    ]);

    $payload = validCandidateProfileUpdatePayload($user->email);

    $this->actingAs($user)
        ->from(route('candidate.applications.show', $application))
        ->patch(route('profile.update', ['stay_on_page' => 1, 'enrollment_step' => 2]), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('candidate.applications.show', $application).'?step=2');
});

test('candidate can replace profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->completeCandidateProfile()->create();
    $user->assignRole('candidato');

    $payload = array_merge(
        validCandidateProfileUpdatePayload($user->email),
        ['foto' => UploadedFile::fake()->image('nova-foto.jpg', 120, 120)],
    );

    $this->actingAs($user)
        ->patch(route('profile.update'), $payload)
        ->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->foto_path)->not->toBeNull();
    Storage::disk('public')->assertExists($user->foto_path);
});

test('authenticated user can view profile photo', function () {
    Storage::fake('local');

    $user = User::factory()->completeCandidateProfile()->create([
        'foto_path' => 'private/profiles/test-photo.jpg',
        'sexo' => 'feminino',
    ]);
    $user->assignRole('candidato');

    Storage::disk('local')->put('private/profiles/test-photo.jpg', 'fake-image');

    $this->actingAs($user)
        ->get(route('profile.photo'))
        ->assertOk();
});

test('cpf cannot be changed via profile update', function () {
    $user = User::factory()->completeCandidateProfile()->create([
        'cpf' => '52998224725',
        'sexo' => 'feminino',
    ]);
    $user->assignRole('candidato');

    $originalCpf = $user->cpf;

    $this->actingAs($user)
        ->patch(route('profile.update'), array_merge(
            validCandidateProfileUpdatePayload($user->email),
            ['cpf' => '11144477735'],
        ))
        ->assertSessionHasNoErrors();

    expect($user->refresh()->cpf)->toBe($originalCpf);
});
