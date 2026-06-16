<?php

namespace App\Modules\Evaluator\Support;

use App\Models\Modules\Candidate\Models\Application;
use App\Support\UserPhotoUrl;

class CandidatePhotoUrl
{
    public static function forApplication(
        Application $application,
        string $privatePhotoRoute = 'evaluator.candidates.photo',
    ): ?string {
        $application->loadMissing('user');

        $user = $application->user;

        if ($user === null) {
            return null;
        }

        $path = $user->foto_path;
        if ($path !== null && str_starts_with((string) $path, 'private/')) {
            return route($privatePhotoRoute, $application);
        }

        return UserPhotoUrl::resolve($user, true);
    }
}
