<?php

namespace App\Http\Controllers\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Admin\StoreProcessTitleItemRequest;
use App\Http\Requests\Modules\Admin\UpdateProcessTitleItemRequest;
use App\Models\Modules\Admin\Models\ProcessTitleGroup;
use App\Models\Modules\Admin\Models\ProcessTitleItem;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Support\InertiaToast;
use Illuminate\Http\RedirectResponse;

class ProcessTitleItemController extends Controller
{
    public function store(
        StoreProcessTitleItemRequest $request,
        SelectionProcess $selectionProcess,
        ProcessTitleGroup $titleGroup
    ): RedirectResponse {
        abort_unless($titleGroup->selection_process_id === $selectionProcess->id, 404);

        $validated = $request->validated();

        $titleGroup->items()->create([
            'code' => strtoupper(trim($validated['code'])),
            'title' => $validated['title'],
            'score_per_unit' => $validated['score_per_unit'],
            'score_unit' => $validated['score_unit'],
            'max_quantity' => $validated['max_quantity'] ?? null,
            'period_rule' => $validated['period_rule'] ?? null,
            'requires_attachment' => $validated['requires_attachment'] ?? true,
            'accepted_formats' => $this->parseFormats($validated['accepted_formats'] ?? null),
            'max_file_size_mb' => $validated['max_file_size_mb'] ?? 10,
            'candidate_instructions' => $validated['candidate_instructions'] ?? null,
            'order' => $validated['order'] ?? 0,
            'is_active' => true,
        ]);

        InertiaToast::success('Item de título adicionado ao grupo.');

        return back();
    }

    public function update(
        UpdateProcessTitleItemRequest $request,
        SelectionProcess $selectionProcess,
        ProcessTitleGroup $titleGroup,
        ProcessTitleItem $item
    ): RedirectResponse {
        abort_unless($titleGroup->selection_process_id === $selectionProcess->id, 404);
        abort_unless($item->process_title_group_id === $titleGroup->id, 404);

        $validated = $request->validated();

        $item->update([
            'code' => strtoupper(trim($validated['code'])),
            'title' => $validated['title'],
            'score_per_unit' => $validated['score_per_unit'],
            'score_unit' => $validated['score_unit'],
            'max_quantity' => $validated['max_quantity'] ?? null,
            'period_rule' => $validated['period_rule'] ?? null,
            'requires_attachment' => $validated['requires_attachment'] ?? true,
            'accepted_formats' => $this->parseFormats($validated['accepted_formats'] ?? null),
            'max_file_size_mb' => $validated['max_file_size_mb'] ?? 10,
            'candidate_instructions' => $validated['candidate_instructions'] ?? null,
            'order' => $validated['order'] ?? $item->order,
        ]);

        InertiaToast::success('Item de título atualizado com sucesso.');

        return back();
    }

    public function destroy(
        SelectionProcess $selectionProcess,
        ProcessTitleGroup $titleGroup,
        ProcessTitleItem $item
    ): RedirectResponse {
        abort_unless($titleGroup->selection_process_id === $selectionProcess->id, 404);
        abort_unless($item->process_title_group_id === $titleGroup->id, 404);

        $item->delete();

        InertiaToast::success('Item de título removido.');

        return back();
    }

    /**
     * @return array<int, string>|null
     */
    private function parseFormats(?string $formats): ?array
    {
        if ($formats === null || trim($formats) === '') {
            return null;
        }

        return collect(explode(',', $formats))
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }
}
