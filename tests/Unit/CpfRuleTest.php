<?php

use App\Rules\Cpf;

test('accepts known valid cpf digits', function () {
    expect(Cpf::passes('52998224725'))->toBeTrue()
        ->and(Cpf::passes('529.982.247-25'))->toBeTrue();
});

test('rejects invalid verifier digits', function () {
    expect(Cpf::passes('52998224724'))->toBeFalse()
        ->and(Cpf::passes('12345678900'))->toBeFalse();
});

test('rejects sequential equal digits', function () {
    expect(Cpf::passes('11111111111'))->toBeFalse();
});

test('rejects wrong length after normalization', function () {
    expect(Cpf::passes('529982247'))->toBeFalse()
        ->and(Cpf::passes(''))->toBeFalse();
});
