<?php

namespace App\Support;

use Inertia\Inertia;

final class InertiaToast
{
    /**
     * @param  array{type: string, message: string, life?: int}  $toast
     */
    public static function flash(array $toast): void
    {
        Inertia::flash('toast', $toast);
    }

    public static function info(string $message, ?int $life = null): void
    {
        self::flash(self::payload('info', $message, $life));
    }

    public static function success(string $message, ?int $life = null): void
    {
        self::flash(self::payload('success', $message, $life));
    }

    public static function warning(string $message, ?int $life = null): void
    {
        self::flash(self::payload('warning', $message, $life));
    }

    public static function error(string $message, ?int $life = null): void
    {
        self::flash(self::payload('error', $message, $life));
    }

    /**
     * @return array{type: string, message: string, life?: int}
     */
    private static function payload(string $type, string $message, ?int $life): array
    {
        $toast = ['type' => $type, 'message' => $message];

        if ($life !== null && $life > 0) {
            $toast['life'] = $life;
        }

        return $toast;
    }
}
