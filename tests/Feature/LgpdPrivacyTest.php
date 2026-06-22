<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

test('privacy policy page can be rendered', function () {
    $this->get(route('privacy-policy.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Legal/PrivacyPolicy')
            ->has('dataController')
            ->has('contactEmail'),
        );
});

test('lgpd config is shared with inertia pages', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('lgpd.data_controller')
            ->has('lgpd.contact_email')
            ->has('lgpd.privacy_policy_url'),
        );
});

test('registration stores lgpd consent timestamp on account creation', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    Notification::fake();
    Storage::fake('public');
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
    ]);

    $email = 'lgpd-consent-'.uniqid().'@example.com';
    $payload = validCandidateRegistrationPayload($email);

    $this->post(route('register.store'), $payload)
        ->assertRedirect(route('verification.notice', absolute: false));

    $user = User::query()->where('email', $email)->firstOrFail();

    expect($user->lgpd_consent_at)->not->toBeNull();
});
