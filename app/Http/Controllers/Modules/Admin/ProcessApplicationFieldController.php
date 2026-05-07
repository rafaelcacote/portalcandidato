<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Admin\StoreProcessApplicationFieldRequest;
use App\Models\Modules\Admin\Models\ProcessApplicationField;
use App\Models\Modules\Admin\Models\SelectionProcess;
use Illuminate\Http\RedirectResponse;

class ProcessApplicationFieldController extends Controller
{
    public function store(
        StoreProcessApplicationFieldRequest $request,
        SelectionProcess $selectionProcess
    ): RedirectResponse {
        $validated = $request->validated();

        $selectionProcess->applicationFields()->create([
            ...$validated,
            'opcoes' => $this->parseOptions($validated['opcoes'] ?? null),
        ]);

        return back()->with('success', 'Campo dinâmico de inscrição adicionado com sucesso.');
    }

    public function destroy(
        SelectionProcess $selectionProcess,
        ProcessApplicationField $processApplicationField
    ): RedirectResponse {
        abort_unless($processApplicationField->selection_process_id === $selectionProcess->id, 404);

        $processApplicationField->delete();

        return back()->with('success', 'Campo dinâmico removido com sucesso.');
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
