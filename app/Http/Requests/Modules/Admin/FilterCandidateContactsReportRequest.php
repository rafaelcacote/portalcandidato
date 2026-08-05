<?php

namespace App\Http\Requests\Modules\Admin;

use App\Models\Modules\Admin\Models\SelectionProcess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterCandidateContactsReportRequest extends FormRequest
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
        return [
            'selection_process_id' => [
                'nullable',
                'integer',
                Rule::exists(SelectionProcess::class, 'id'),
            ],
        ];
    }

    /**
     * @return array{selection_process_id: int|null}
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'selection_process_id' => $this->integerOrNull($validated['selection_process_id'] ?? null),
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
