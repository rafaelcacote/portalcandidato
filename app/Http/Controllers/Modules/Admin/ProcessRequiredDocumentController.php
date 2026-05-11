<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Admin\StoreProcessRequiredDocumentRequest;
use App\Models\Modules\Admin\Models\ProcessRequiredDocument;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Admin\Models\TipoDocumento;
use App\Support\InertiaToast;
use Illuminate\Http\RedirectResponse;

class ProcessRequiredDocumentController extends Controller
{
    public function store(
        StoreProcessRequiredDocumentRequest $request,
        SelectionProcess $selectionProcess
    ): RedirectResponse {
        $validated = $request->validated();
        $tipoDocumento = TipoDocumento::query()->findOrFail($validated['tipo_documento_id']);

        $selectionProcess->requiredDocuments()->create([
            'tipo_documento_id' => $tipoDocumento->id,
            'nome' => $tipoDocumento->descricao,
            'descricao' => $validated['descricao'] ?? null,
            'formatos_aceitos' => $this->parseOptions($validated['formatos_aceitos'] ?? null),
            'tamanho_max_mb' => $validated['tamanho_max_mb'],
            'obrigatorio' => $validated['obrigatorio'],
            'gerado_por_template' => false,
        ]);

        InertiaToast::success('Documento exigido vinculado ao processo.');

        return back();
    }

    public function destroy(
        SelectionProcess $selectionProcess,
        ProcessRequiredDocument $processRequiredDocument
    ): RedirectResponse {
        abort_unless($processRequiredDocument->selection_process_id === $selectionProcess->id, 404);

        $processRequiredDocument->delete();

        InertiaToast::success('Documento exigido removido do processo.');

        return back();
    }

    /**
     * @return array<int, string>|null
     */
    private function parseOptions(?string $options): ?array
    {
        if ($options === null || trim($options) === '') {
            return null;
        }

        return collect(explode(',', $options))
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }
}
