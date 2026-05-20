<?php

use App\Models\User;
use App\Support\BrazilianStates;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

/**
 * @return array<string, mixed>
 */
function validCandidateRegistrationPayload(string $email): array
{
    return [
        'name' => 'Candidato Teste',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
        'data_nascimento' => '1995-06-15',
        'cpf' => '529.982.247-25',
        'identidade' => '12.345.678-9',
        'orgao_emissor' => 'SSP',
        'identidade_uf' => 'SP',
        'identidade_data_emissao' => '2018-03-20',
        'naturalidade' => 'São Paulo',
        'nacionalidade' => 'Brasileira',
        'sexo' => 'feminino',
        'endereco' => 'Rua das Flores',
        'endereco_numero' => '100',
        'bairro' => 'Centro',
        'cep' => '01310-100',
        'cidade' => 'São Paulo',
        'endereco_uf' => 'SP',
        'pais' => 'Brasil',
        'telefone' => '(11) 98888-7777',
        'telefone_fixo' => '',
        'foto' => UploadedFile::fake()->image('avatar.jpg', 100, 100),
    ];
}

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/Register')
        ->has('ufs'),
    );
});

test('registration screen exposes brazilian state abbreviations', function () {
    $this->get(route('register'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('ufs', BrazilianStates::abbreviations()),
        );
});

test('new candidates can register and are redirected to email verification', function () {
    Notification::fake();
    Storage::fake('public');

    $email = 'cand-'.uniqid().'@example.com';

    $response = $this->post(route('register.store'), validCandidateRegistrationPayload($email));

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice', absolute: false));

    $user = User::query()->where('email', $email)->firstOrFail();
    expect($user->hasRole('candidato'))->toBeTrue()
        ->and($user->cpf)->toBe('52998224725')
        ->and($user->cep)->toBe('01310100')
        ->and($user->endereco)->toBe('Rua das Flores')
        ->and($user->hasVerifiedEmail())->toBeFalse()
        ->and($user->foto_path)->not->toBeNull();

    Storage::disk('public')->assertExists($user->foto_path);

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('unverified candidates cannot access candidate dashboard', function () {
    Role::findOrCreate('candidato', 'web');

    $user = User::factory()->unverified()->create();
    $user->assignRole('candidato');

    $this->actingAs($user)
        ->get(route('candidate.dashboard'))
        ->assertRedirect(route('verification.notice', absolute: false));
});

test('registration stores required profile photo', function () {
    Storage::fake('public');

    $email = 'cand-photo-'.uniqid().'@example.com';

    $payload = validCandidateRegistrationPayload($email);
    $payload['foto'] = UploadedFile::fake()->image('avatar.jpg', 100, 100);

    $this->post(route('register.store'), $payload)
        ->assertRedirect(route('verification.notice', absolute: false));

    $user = User::query()->where('email', $email)->firstOrFail();

    expect($user->foto_path)->not->toBeNull();
    Storage::disk('public')->assertExists($user->foto_path);
});

test('registration rejects missing profile photo', function () {
    $email = 'cand-no-photo-'.uniqid().'@example.com';
    $payload = validCandidateRegistrationPayload($email);
    unset($payload['foto']);

    $this->from(route('register'))
        ->post(route('register.store'), $payload)
        ->assertSessionHasErrors('foto');

    $this->assertGuest();
});

test('registration rejects invalid cpf', function () {
    $email = 'cand-bad-'.uniqid().'@example.com';
    $payload = validCandidateRegistrationPayload($email);
    $payload['cpf'] = '123.456.789-00';

    $this->from(route('register'))
        ->post(route('register.store'), $payload)
        ->assertSessionHasErrors('cpf');

    $this->assertGuest();
});

test('registration rejects duplicate cpf with portuguese message', function () {
    User::factory()->create([
        'email' => 'existing-'.uniqid().'@example.com',
        'cpf' => '52998224725',
    ]);

    $email = 'cand-dup-'.uniqid().'@example.com';
    $payload = validCandidateRegistrationPayload($email);

    $this->from(route('register'))
        ->post(route('register.store'), $payload)
        ->assertSessionHasErrors(['cpf' => 'Este CPF já está cadastrado.']);

    $this->assertGuest();
});
