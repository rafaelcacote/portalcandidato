<?php

namespace App\Http\Requests\Modules\Admin;

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Modules\Candidate\Support\ResearchLineCatalog;
use App\Modules\Shared\Enums\ApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterEnrolledCandidatesReportRequest extends FormRequest
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
        $selectionProcessId = $this->selectionProcess()?->id;
        $lineKeys = ResearchLineCatalog::lineKeys($selectionProcessId);
        $statusValues = collect(ApplicationStatus::cases())
            ->reject(fn (ApplicationStatus $status): bool => $status === ApplicationStatus::Rascunho)
            ->map(fn (ApplicationStatus $status): string => $status->value)
            ->all();

        return [
            'search' => ['nullable', 'string', 'max:255'],
            'pcd' => ['nullable', 'string', Rule::in(['all', 'sim', 'nao'])],
            'vinculo' => ['nullable', 'string', Rule::in(['all', 'sem_vinculo', 'com_vinculo'])],
            'linha_pesquisa' => ['nullable', 'string', Rule::in($lineKeys)],
            'orientador' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in([...$statusValues, 'all'])],
        ];
    }

    /**
     * @return array{
     *     search: string,
     *     pcd: string,
     *     vinculo: string,
     *     linha_pesquisa: string,
     *     orientador: string,
     *     status: string
     * }
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'search' => trim((string) ($validated['search'] ?? '')),
            'pcd' => $this->normalizedChoice($validated['pcd'] ?? null),
            'vinculo' => $this->normalizedChoice($validated['vinculo'] ?? null),
            'linha_pesquisa' => trim((string) ($validated['linha_pesquisa'] ?? '')),
            'orientador' => trim((string) ($validated['orientador'] ?? '')),
            'status' => $this->normalizedChoice($validated['status'] ?? null),
        ];
    }

    private function normalizedChoice(mixed $value): string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? 'all' : $normalized;
    }

    private function selectionProcess(): ?SelectionProcess
    {
        $selectionProcess = $this->route('selectionProcess');

        return $selectionProcess instanceof SelectionProcess ? $selectionProcess : null;
    }
}
