<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\UserPhotoUrl;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $this->serializeAuthUser($request->user()),
                'roles' => $request->user()?->getRoleNames()->values()->all(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'ui' => [
                'settings' => __('ui.settings', [], 'pt_BR'),
                'log_out' => __('ui.log_out', [], 'pt_BR'),
            ],
            'candidateProfileComplete' => $this->candidateProfileComplete($request),
            'lgpd' => [
                'data_controller' => config('lgpd.data_controller'),
                'contact_email' => config('lgpd.contact_email'),
                'privacy_policy_url' => route('privacy-policy.show'),
            ],
        ];
    }

    private function candidateProfileComplete(Request $request): bool
    {
        $user = $request->user();
        if ($user === null) {
            return true;
        }

        return $user->candidateProfileIsComplete();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function serializeAuthUser(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        $photoUrl = UserPhotoUrl::resolve($user, true);

        return array_merge($user->toArray(), [
            'avatar' => $photoUrl,
            'foto_url' => $photoUrl,
        ]);
    }
}
