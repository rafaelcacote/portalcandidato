<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Turnstile implements ValidationRule
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('Por favor, complete a verificação de segurança.');

            return;
        }

        try {
            $response = Http::asForm()->post(self::VERIFY_URL, [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);

            if (! $response->successful() || ! $response->json('success')) {
                $fail('A verificação de segurança falhou. Tente novamente.');
            }
        } catch (\Throwable $e) {
            Log::error('Turnstile verification failed', ['error' => $e->getMessage()]);
            $fail('Não foi possível verificar a segurança. Tente novamente.');
        }
    }
}
