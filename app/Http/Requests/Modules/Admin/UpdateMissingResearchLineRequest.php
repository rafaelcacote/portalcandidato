<?php

namespace App\Http\Requests\Modules\Admin;

use App\Models\Modules\Candidate\Models\Application;
use App\Modules\Candidate\Support\ResearchLineCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * TEMPORÁRIO — remover após preencher linhas de pesquisa dos candidatos legados.
 */
class UpdateMissingResearchLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        $selectionProcessId = $this->application()?->selection_process_id;

        return [
            'linha_pesquisa' => ['required', 'string', Rule::in(ResearchLineCatalog::lineKeys($selectionProcessId))],
            'orientador' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $linhaPesquisa = (string) $this->input('linha_pesquisa', '');
            $orientador = (string) $this->input('orientador', '');

            if ($linhaPesquisa === '' || $orientador === '') {
                return;
            }

            $selectionProcessId = $this->application()?->selection_process_id;

            if (! ResearchLineCatalog::isValidAdvisor($linhaPesquisa, $orientador, $selectionProcessId)) {
                $validator->errors()->add(
                    'orientador',
                    'O orientador selecionado não é válido para a linha de pesquisa escolhida.',
                );
            }
        });
    }

    private function application(): ?Application
    {
        $application = $this->route('application');

        return $application instanceof Application ? $application : null;
    }
}
