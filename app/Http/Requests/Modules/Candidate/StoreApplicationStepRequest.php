<?php

namespace App\Http\Requests\Modules\Candidate;

use App\Models\Modules\Candidate\Models\Application;
use App\Modules\Candidate\Support\ResearchLineCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreApplicationStepRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        $step = (int) $this->route('step', 0);

        if ($step === 1) {
            return [
                'payload' => ['required', 'array'],
                'payload.concorre_vagas_pcd' => ['required', 'boolean'],
            ];
        }

        if ($step === 2) {
            return [
                'payload' => ['required', 'array'],
                'payload.concorre_vagas_sem_vinculo' => ['required', 'boolean'],
            ];
        }

        if ($step === 3) {
            $selectionProcessId = $this->application()?->selection_process_id;

            return [
                'payload' => ['required', 'array'],
                'payload.linha_pesquisa' => ['required', 'string', Rule::in(ResearchLineCatalog::lineKeys($selectionProcessId))],
                'payload.orientador' => ['required', 'string', 'max:255'],
            ];
        }

        return [
            'payload' => ['required', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $step = (int) $this->route('step', 0);

            if ($step !== 3) {
                return;
            }

            $linhaPesquisa = (string) $this->input('payload.linha_pesquisa', '');
            $orientador = (string) $this->input('payload.orientador', '');

            if ($linhaPesquisa === '' || $orientador === '') {
                return;
            }

            $selectionProcessId = $this->application()?->selection_process_id;

            if (! ResearchLineCatalog::isValidAdvisor($linhaPesquisa, $orientador, $selectionProcessId)) {
                $validator->errors()->add(
                    'payload.orientador',
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
