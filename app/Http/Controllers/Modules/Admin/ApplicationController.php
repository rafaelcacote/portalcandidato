<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modules\Candidate\Models\Application;
use App\Modules\Evaluator\Support\CandidatePhotoUrl;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    public function index(): Response
    {
        $applications = Application::query()
            ->with([
                'selectionProcess:id,titulo,status',
                'user:id,name,email,foto_path',
            ])
            ->latest('updated_at')
            ->paginate(15);

        $applications->getCollection()->transform(fn (Application $application): array => [
            'id' => $application->id,
            'status' => $application->status,
            'numero_protocolo' => $application->numero_protocolo,
            'finalizada_em' => $application->finalizada_em?->toIso8601String(),
            'created_at' => $application->created_at?->toIso8601String(),
            'updated_at' => $application->updated_at?->toIso8601String(),
            'selection_process' => $application->selectionProcess === null
                ? null
                : [
                    'id' => $application->selectionProcess->id,
                    'titulo' => $application->selectionProcess->titulo,
                    'status' => $application->selectionProcess->status,
                ],
            'candidate' => $application->user === null
                ? null
                : [
                    'id' => $application->user->id,
                    'name' => $application->user->name,
                    'email' => $application->user->email,
                    'photo_url' => CandidatePhotoUrl::forApplication(
                        $application,
                        'admin.applications.photo',
                    ),
                ],
        ]);

        return Inertia::render('Admin/Applications/Index', [
            'applications' => $applications,
        ]);
    }

    public function viewPhoto(Application $application): StreamedResponse|HttpResponse
    {
        $application->loadMissing('user');

        $path = $application->user?->foto_path;
        abort_if($path === null || trim((string) $path) === '', 404);

        $path = (string) $path;

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return redirect()->away($path);
        }

        foreach (['public', (string) config('filesystems.default', 'local')] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->response($path);
            }
        }

        abort(404);
    }
}
