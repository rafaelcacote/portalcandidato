<?php

namespace App\Http\Controllers\Modules\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Candidate\StoreApplicationDocumentRequest;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use Illuminate\Http\RedirectResponse;

class DocumentController extends Controller
{
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
