<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class UserPhotoUrl
{
    /**
     * Resolves a display URL for the user's profile photo.
     *
     * Public-disk files use a relative `/storage/...` URL so the URL stays
     * valid regardless of port (dev server, artisan serve, etc.).
     * Private-disk paths are served via an authenticated controller action.
     */
    public static function resolve(?User $user, ?bool $forAuthenticatedViewer = null): ?string
    {
        if ($user === null) {
            return null;
        }

        $path = $user->foto_path;
        if ($path === null || trim((string) $path) === '') {
            return null;
        }

        $path = (string) $path;

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (! str_starts_with($path, 'private/')) {
            // Use a relative path so it works on any port (artisan serve, vite, nginx)
            if (! Storage::disk('public')->exists($path)) {
                return null;
            }

            return '/storage/'.ltrim($path, '/');
        }

        // Private file: only the authenticated owner can view it
        $canViewPrivate = $forAuthenticatedViewer ?? auth()->id() === $user->id;

        if (! $canViewPrivate) {
            return null;
        }

        // Strip the 'private/' prefix that is already the disk root
        $diskPath = preg_replace('#^private/#', '', $path);

        if (! Storage::disk('local')->exists($diskPath)) {
            return null;
        }

        return route('profile.photo');
    }
}
