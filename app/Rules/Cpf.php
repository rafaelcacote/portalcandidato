<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cpf implements ValidationRule
{
    public static function normalizeToDigits(mixed $value): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return '';
        }

        return preg_replace('/\D/', '', (string) $value) ?? '';
    }

    public static function passes(mixed $value): bool
    {
        $digits = self::normalizeToDigits($value);

        return self::digitsAreValid($digits);
    }

    public static function maskForDisplay(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $digits = self::normalizeToDigits($value);

        if (strlen($digits) < 11) {
            return $value;
        }

        return substr($digits, 0, 3).'.***.***-'.substr($digits, -2);
    }

    public static function digitsAreValid(string $digits): bool
    {
        if (strlen($digits) !== 11) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $digits) === 1) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $d = 0;

            for ($c = 0; $c < $t; $c++) {
                $d += (int) $digits[$c] * (($t + 1) - $c);
            }

            $d = ((10 * $d) % 11) % 10;

            if ((int) $digits[$t] !== $d) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  Closure(string): void  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::passes($value)) {
            $fail('Informe um CPF válido.');
        }
    }
}
