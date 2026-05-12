<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('register check cpf returns incomplete when cpf has fewer than 11 digits', function (): void {
    $this->getJson(route('register.check-cpf', ['cpf' => '529']))
        ->assertOk()
        ->assertJson(['status' => 'incomplete']);
});

test('register check cpf returns invalid for invalid check digits', function (): void {
    $this->getJson(route('register.check-cpf', ['cpf' => '11111111111']))
        ->assertOk()
        ->assertJson(['status' => 'invalid']);
});

test('register check cpf returns taken when cpf is already registered', function (): void {
    User::factory()->create([
        'email' => 'existing@example.com',
        'cpf' => '52998224725',
    ]);

    $this->getJson(route('register.check-cpf', ['cpf' => '52998224725']))
        ->assertOk()
        ->assertJson(['status' => 'taken']);
});

test('register check cpf returns available for valid unused cpf', function (): void {
    $this->getJson(route('register.check-cpf', ['cpf' => '52998224725']))
        ->assertOk()
        ->assertJson(['status' => 'available']);
});
