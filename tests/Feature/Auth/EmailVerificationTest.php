<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::emailVerification());
});

test('email verification screen can be rendered', function () {
    Role::findOrCreate('candidato', 'web');

    $user = User::factory()->unverified()->create();
    $user->assignRole('candidato');

    $response = $this->actingAs($user)->get(route('verification.notice'));

    $response->assertOk();
});

test('unverified admin can access admin dashboard without email verification', function () {
    Role::findOrCreate('admin', 'web');

    $admin = User::factory()->unverified()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful();
});

test('unverified evaluator can access evaluator dashboard without email verification', function () {
    Role::findOrCreate('avaliador', 'web');

    $evaluator = User::factory()->unverified()->create();
    $evaluator->assignRole('avaliador');

    $this->actingAs($evaluator)
        ->get(route('evaluator.dashboard'))
        ->assertSuccessful();
});

test('unverified user can logout from email verification screen', function () {
    Role::findOrCreate('candidato', 'web');

    $user = User::factory()->unverified()->create();
    $user->assignRole('candidato');

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('guest can verify email via signed link without being logged in first', function () {
    Role::findOrCreate('candidato', 'web');

    $user = User::factory()->unverified()->create();
    $user->assignRole('candidato');

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())],
    );

    $this->get($verificationUrl)
        ->assertRedirect(route('candidate.dashboard', absolute: false).'?verified=1');

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->assertAuthenticatedAs($user);
});

test('email verification ignores stale intended admin url in session', function () {
    Role::findOrCreate('candidato', 'web');
    Role::findOrCreate('admin', 'web');

    $user = User::factory()->unverified()->create();
    $user->assignRole('candidato');

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())],
    );

    $this->withSession(['url.intended' => route('admin.dashboard')])
        ->get($verificationUrl)
        ->assertRedirect(route('candidate.dashboard', absolute: false).'?verified=1');

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('email can be verified', function () {
    Role::findOrCreate('candidato', 'web');

    $user = User::factory()->unverified()->create();
    $user->assignRole('candidato');

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('candidate.dashboard', absolute: false).'?verified=1');
});

test('email is not verified with invalid hash', function () {
    Role::findOrCreate('candidato', 'web');

    $user = User::factory()->unverified()->create();
    $user->assignRole('candidato');

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')],
    );

    $this->actingAs($user)->get($verificationUrl);

    Event::assertNotDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('email is not verified with invalid user id', function () {
    Role::findOrCreate('candidato', 'web');

    $user = User::factory()->unverified()->create();
    $user->assignRole('candidato');

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => 123, 'hash' => sha1($user->email)],
    );

    $this->actingAs($user)->get($verificationUrl);

    Event::assertNotDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('verified user is redirected to dashboard from verification prompt', function () {
    Role::findOrCreate('candidato', 'web');

    $user = User::factory()->create();
    $user->assignRole('candidato');

    Event::fake();

    $response = $this->actingAs($user)->get(route('verification.notice'));

    Event::assertNotDispatched(Verified::class);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('already verified user visiting verification link is redirected without firing event again', function () {
    Role::findOrCreate('candidato', 'web');

    $user = User::factory()->create();
    $user->assignRole('candidato');

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $this->actingAs($user)->get($verificationUrl)
        ->assertRedirect(route('candidate.dashboard', absolute: false).'?verified=1');

    Event::assertNotDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});
