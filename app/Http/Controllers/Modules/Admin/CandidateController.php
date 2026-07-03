<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\UserPhotoUrl;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateController extends Controller
{
    public function index(): Response
    {
        $candidates = User::query()
            ->role('candidato')
            ->withCount('applications')
            ->orderBy('name')
            ->get()
            ->map(fn (User $candidate): array => $this->serializeCandidateListItem($candidate))
            ->values()
            ->all();

        return Inertia::render('Admin/Candidates/Index', [
            'candidates' => $candidates,
        ]);
    }

    public function show(User $candidate): Response
    {
        abort_unless($candidate->hasRole('candidato'), 404);

        $candidate->loadCount('applications');

        return Inertia::render('Admin/Candidates/Show', [
            'candidate' => $this->serializeCandidateListItem($candidate),
            'profile' => $this->candidateProfilePayload($candidate),
        ]);
    }

    public function viewPhoto(User $candidate): StreamedResponse|HttpResponse
    {
        abort_unless($candidate->hasRole('candidato'), 404);

        $path = $candidate->foto_path;
        abort_if($path === null || trim((string) $path) === '', 404);

        $path = (string) $path;

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return redirect()->away($path);
        }

        if (str_starts_with($path, 'private/')) {
            $diskPath = preg_replace('#^private/#', '', $path);
            abort_unless(Storage::disk('local')->exists($diskPath), 404);

            return Storage::disk('local')->response($diskPath);
        }

        foreach (['public', (string) config('filesystems.default', 'local')] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->response($path);
            }
        }

        abort(404);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCandidateListItem(User $candidate): array
    {
        return [
            'id' => $candidate->id,
            'name' => $candidate->name,
            'email' => $candidate->email,
            'cpf' => $candidate->cpf,
            'telefone' => $candidate->telefone,
            'ativo' => $candidate->ativo,
            'email_verified' => $candidate->hasVerifiedEmail(),
            'profile_complete' => $candidate->candidateProfileIsComplete(),
            'applications_count' => $candidate->applications_count ?? $candidate->applications()->count(),
            'created_at' => $candidate->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function candidateProfilePayload(User $user): array
    {
        $path = $user->foto_path;
        $fotoUrl = UserPhotoUrl::resolve($user, true);

        if ($path !== null && str_starts_with((string) $path, 'private/')) {
            $fotoUrl = route('admin.candidates.photo', $user);
        }

        return [
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'cpf' => $user->cpf,
            'telefone' => $user->telefone,
            'telefone_fixo' => $user->telefone_fixo,
            'data_nascimento' => $user->data_nascimento?->format('Y-m-d'),
            'foto_url' => $fotoUrl,
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
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];
    }
}
