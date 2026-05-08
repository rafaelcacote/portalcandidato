<?php

namespace App\Support;

use Inertia\Inertia;

final class InertiaToast
{
    /**
     * @param  array{type: string, message: string}  $toast
     */
    public static function flash(array $toast): void
    {
        Inertia::flash('toast', $toast);
    }

    public static function info(string $message): void
    {
        self::flash(['type' => 'info', 'message' => $message]);
    }

    public static function success(string $message): void
    {
        self::flash(['type' => 'success', 'message' => $message]);
    }

    public static function warning(string $message): void
    {
        self::flash(['type' => 'warning', 'message' => $message]);
    }

    public static function error(string $message): void
    {
        self::flash(['type' => 'error', 'message' => $message]);
    }
}
