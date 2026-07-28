<?php

namespace App\Http\Requests\Modules\Admin;

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Modules\Candidate\Support\ResearchLineCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterEvaluatedCandidatesReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $selectionProcessId = $this->integerOrNull($this->input('selection_process_id'));

        return [
            'selection_process_id' => [
                'nullable',
                'integer',
                Rule::exists(SelectionProcess::class, 'id'),
            ],
            'linha_pesquisa' => [
                'nullable',
                'string',
                Rule::in(ResearchLineCatalog::lineKeys($selectionProcessId)),
            ],
        ];
    }

    /**
     * @return array{
     *     selection_process_id: int|null,
     *     linha_pesquisa: string
     * }
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'selection_process_id' => $this->integerOrNull($validated['selection_process_id'] ?? null),
            'linha_pesquisa' => trim((string) ($validated['linha_pesquisa'] ?? '')),
        ];
    }

    private function integerOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
