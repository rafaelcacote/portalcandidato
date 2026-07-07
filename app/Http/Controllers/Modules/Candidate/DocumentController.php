<?php

namespace App\Http\Controllers\Modules\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Candidate\StoreApplicationDocumentRequest;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Modules\Candidate\Enums\CandidaturaSpecialDocumentKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            ->with([
                'selectionProcess:id,titulo,status,inscricao_inicio_em,inscricao_fim_em',
                'selectionProcess.requiredDocuments',
            ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (Application $app): array => [
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
                'can_upload_documents' => $app->canModifyDocuments(),
            ]);

        return Inertia::render('Candidate/Documents/Index', [
            'documents' => $documents,
            'applications' => $applications,
            'has_uploadable_applications' => $applications->contains(
                fn (array $application): bool => $application['can_upload_documents'],
            ),
        ]);
    }

    public function store(Application $application, StoreApplicationDocumentRequest $request): RedirectResponse
    {
        abort_if($application->user_id !== auth()->id(), 403);
        abort_unless(
            $application->canModifyDocuments(),
            422,
            $application->canModifyEnrollment()
                ? 'Não é possível enviar ou alterar documentos após a finalização da inscrição.'
                : 'As inscrições para este processo seletivo estão encerradas.',
        );

        $validated = $request->validated();
        $file = $request->file('arquivo');
        $path = $file->store("private/documents/{$application->selection_process_id}/{$application->id}");

        $kind = isset($validated['candidatura_document_kind'])
            ? CandidaturaSpecialDocumentKind::tryFrom((string) $validated['candidatura_document_kind'])
            : null;

        if ($kind !== null) {
            $this->replaceExisting(
                $application->id,
                fn ($q) => $q->where('candidatura_document_kind', $kind->value),
            );

            ApplicationDocument::query()->create([
                'application_id' => $application->id,
                'candidatura_document_kind' => $kind->value,
                'process_required_document_id' => null,
                'process_title_item_id' => null,
                'caminho' => $path,
                'nome_arquivo' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType() ?? 'application/octet-stream',
                'status' => 'enviado',
            ]);

            return back()->with('success', 'Documento enviado.');
        }

        if ($request->filled('process_title_item_id')) {
            $titleItemId = $request->integer('process_title_item_id');

            ApplicationDocument::query()->create([
                'application_id' => $application->id,
                'process_required_document_id' => null,
                'process_title_item_id' => $titleItemId,
                'quantidade' => 1,
                'caminho' => $path,
                'nome_arquivo' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType() ?? 'application/octet-stream',
                'status' => 'enviado',
            ]);

            return back()->with('success', 'Comprovante de título enviado.');
        }

        ApplicationDocument::query()->create([
            'application_id' => $application->id,
            'process_required_document_id' => $request->integer('process_required_document_id'),
            'process_title_item_id' => null,
            'caminho' => $path,
            'nome_arquivo' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
            'status' => 'enviado',
        ]);

        return back()->with('success', 'Documento enviado.');
    }

    public function show(Application $application, ApplicationDocument $document): StreamedResponse|HttpResponse
    {
        abort_if($application->user_id !== auth()->id(), 403);
        abort_if($document->application_id !== $application->id, 404);
        abort_unless(Storage::exists($document->caminho), 404);

        return Storage::response($document->caminho, $document->nome_arquivo);
    }

    public function destroy(Application $application, ApplicationDocument $document): RedirectResponse
    {
        abort_if($application->user_id !== auth()->id(), 403);
        abort_if($document->application_id !== $application->id, 404);
        abort_unless(
            $application->canModifyDocuments(),
            422,
            $application->canModifyEnrollment()
                ? 'Não é possível enviar ou alterar documentos após a finalização da inscrição.'
                : 'As inscrições para este processo seletivo estão encerradas.',
        );

        if ($document->caminho !== '') {
            Storage::delete($document->caminho);
        }

        $document->delete();

        return back()->with('success', 'Comprovante removido.');
    }

    /**
     * Deletes any pre-existing document records (and their stored files) that
     * match the given scope so a fresh upload replaces the previous version.
     *
     * @param  \Closure(Builder): Builder  $scope
     */
    private function replaceExisting(int $applicationId, \Closure $scope): void
    {
        $query = ApplicationDocument::query()->where('application_id', $applicationId);
        $scope($query);

        $query->get()->each(function (ApplicationDocument $document): void {
            if ($document->caminho !== '') {
                Storage::delete($document->caminho);
            }
            $document->delete();
        });
    }
}
