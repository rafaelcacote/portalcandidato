<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Admin\StoreProcessRequiredTituloRequest;
use App\Models\Modules\Admin\Models\ProcessRequiredTitulo;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Support\InertiaToast;
use Illuminate\Http\RedirectResponse;

class ProcessRequiredTituloController extends Controller
{
    public function store(
        StoreProcessRequiredTituloRequest $request,
        SelectionProcess $selectionProcess
    ): RedirectResponse {
        $validated = $request->validated();

        $selectionProcess->requiredTitulos()->create([
            'tipo_titulo_id' => $validated['tipo_titulo_id'],
            'pontuacao_max' => $validated['pontuacao_max'],
            'qtd_maxima' => $validated['qtd_maxima'] ?? null,
            'obrigatorio' => $validated['obrigatorio'],
            'formatos_aceitos' => $this->parseOptions($validated['formatos_aceitos'] ?? null),
            'tamanho_max_mb' => $validated['tamanho_max_mb'],
            'descricao' => $validated['descricao'] ?? null,
        ]);

        InertiaToast::success('Título aceito vinculado ao processo.');

        return back();
    }

    public function destroy(
        SelectionProcess $selectionProcess,
        ProcessRequiredTitulo $processRequiredTitulo
    ): RedirectResponse {
        abort_unless($processRequiredTitulo->selection_process_id === $selectionProcess->id, 404);

        $processRequiredTitulo->delete();

        InertiaToast::success('Título aceito removido do processo.');

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
