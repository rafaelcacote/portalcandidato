<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CandidateProfileUpdateRequest;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use App\Support\BrazilianStates;
use App\Support\UserPhotoUrl;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail
                && $user->mustVerifyEmailAddress(),
            'status' => $request->session()->get('status'),
            'isCandidate' => $user->hasRole('candidato'),
            'ufs' => $user->hasRole('candidato') ? BrazilianStates::abbreviations() : [],
            'profile' => $user->hasRole('candidato') ? $this->candidateProfilePayload($user) : null,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        if ($request->user()->hasRole('candidato')) {
            return $this->updateCandidateProfile(
                CandidateProfileUpdateRequest::createFrom($request)
                    ->setContainer(app())
                    ->setRedirector(app('redirect'))
                    ->setUserResolver(fn () => $request->user())
            );
        }

        return $this->updateBasicProfile(
            ProfileUpdateRequest::createFrom($request)
                ->setContainer(app())
                ->setRedirector(app('redirect'))
                ->setUserResolver(fn () => $request->user())
        );
    }

    public function viewPhoto(Request $request): StreamedResponse|HttpResponse
    {
        $user = $request->user();
        $path = $user->foto_path;
        abort_if($path === null || trim((string) $path) === '', 404);

        $path = (string) $path;

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return redirect()->away($path);
        }

        // Public-disk photos are served as static files via the storage symlink,
        // so this route is only reached for private-disk paths.
        if (str_starts_with($path, 'private/')) {
            // Strip prefix: local disk root is already storage/app/private
            $diskPath = preg_replace('#^private/#', '', $path);
            abort_unless(Storage::disk('local')->exists($diskPath), 404);

            return Storage::disk('local')->response($diskPath);
        }

        // Fallback: try both disks for any unrecognised path
        foreach (['public', 'local'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->response($path);
            }
        }

        abort(404);
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function updateBasicProfile(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->validateResolved();

        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email') && $request->user()->mustVerifyEmailAddress()) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    private function updateCandidateProfile(CandidateProfileUpdateRequest $request): RedirectResponse
    {
        $request->validateResolved();

        $user = $request->user();
        $validated = $request->validated();

        /** @var UploadedFile|null $foto */
        $foto = $validated['foto'] ?? null;
        unset($validated['foto']);

        $user->fill($validated);

        if ($user->isDirty('email') && $user->mustVerifyEmailAddress()) {
            $user->email_verified_at = null;
        }

        if ($foto instanceof UploadedFile) {
            $this->deleteStoredPhoto($user->foto_path);

            $user->foto_path = $foto->store('candidate-photos/'.$user->id, 'public');
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Perfil atualizado com sucesso.']);

        return to_route('profile.edit');
    }

    /**
     * @return array<string, mixed>
     */
    private function candidateProfilePayload(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'cpf' => $user->cpf,
            'telefone' => $user->telefone,
            'telefone_fixo' => $user->telefone_fixo,
            'data_nascimento' => $user->data_nascimento?->format('Y-m-d'),
            'foto_url' => UserPhotoUrl::resolve($user, true),
            'identidade' => $user->identidade,
            'orgao_emissor' => $user->orgao_emissor,
            'identidade_uf' => $user->identidade_uf,
            'identidade_data_emissao' => $user->identidade_data_emissao?->format('Y-m-d'),
            'naturalidade' => $user->naturalidade,
            'nacionalidade' => $user->nacionalidade,
            'sexo' => $user->sexo,
            'endereco' => $user->endereco,
            'endereco_numero' => $user->endereco_numero,
            'bairro' => $user->bairro,
            'cep' => $user->cep,
            'cidade' => $user->cidade,
            'endereco_uf' => $user->endereco_uf,
            'pais' => $user->pais,
            'updated_at' => $user->updated_at,
        ];
    }

    private function deleteStoredPhoto(?string $path): void
    {
        if ($path === null || trim($path) === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        foreach (['public', (string) config('filesystems.default', 'local')] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }
}
