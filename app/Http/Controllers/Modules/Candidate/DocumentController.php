<?php

namespace App\Http\Controllers\Modules\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Candidate\StoreApplicationDocumentRequest;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $documents = ApplicationDocument::query()
            ->with(['application.selectionProcess'])
            ->whereHas('application', fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest()
            ->get();

        $applications = Application::query()
            ->with(['selectionProcess', 'selectionProcess.requiredDocuments'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (Application $app) => [
                'id' => $app->id,
                'numero_protocolo' => $app->numero_protocolo,
                'selection_process' => $app->selectionProcess ? [
                    'id' => $app->selectionProcess->id,
                    'titulo' => $app->selectionProcess->titulo,
                ] : null,
                'required_documents' => $app->selectionProcess?->requiredDocuments->map(fn ($d) => [
                    'id' => $d->id,
                    'nome' => $d->nome,
                    'obrigatorio' => $d->obrigatorio,
                ])->values() ?? [],
            ]);

        return Inertia::render('Candidate/Documents/Index', [
            'documents' => $documents,
            'applications' => $applications,
        ]);
    }

    public function store(Application $application, StoreApplicationDocumentRequest $request): RedirectResponse
    {
        abort_if($application->user_id !== auth()->id(), 403);

        $file = $request->file('arquivo');
        $path = $file->store("private/documents/{$application->selection_process_id}/{$application->id}");

        ApplicationDocument::query()->create([
            'application_id' => $application->id,
            'process_required_document_id' => $request->integer('process_required_document_id'),
            'caminho' => $path,
            'nome_arquivo' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
            'status' => 'enviado',
        ]);

        return back()->with('success', 'Documento enviado.');
    }
}
