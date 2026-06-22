<?php

use App\Models\User;
use App\Support\BrazilianStates;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());

    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
    ]);
});

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

test('registration rejects mismatched email confirmation with portuguese message', function () {
    $email = 'cand-email-mismatch-'.uniqid().'@example.com';
    $payload = validCandidateRegistrationPayload($email);
    $payload['email_confirmation'] = 'outro-'.uniqid().'@example.com';

    $this->from(route('register'))
        ->post(route('register.store'), $payload)
        ->assertSessionHasErrors(['email' => 'A confirmação de e-mail não confere.']);

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

test('registration rejects missing turnstile token', function () {
    $email = 'cand-no-turnstile-'.uniqid().'@example.com';
    $payload = validCandidateRegistrationPayload($email);
    unset($payload['turnstile_token']);

    $this->from(route('register'))
        ->post(route('register.store'), $payload)
        ->assertSessionHasErrors('turnstile_token');

    $this->assertGuest();
});

test('registration rejects invalid turnstile token', function () {
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => false], 200),
    ]);

    $email = 'cand-bad-turnstile-'.uniqid().'@example.com';
    $payload = validCandidateRegistrationPayload($email);
    $payload['turnstile_token'] = 'invalid-token';

    $this->from(route('register'))
        ->post(route('register.store'), $payload)
        ->assertSessionHasErrors('turnstile_token');

    $this->assertGuest();
});
