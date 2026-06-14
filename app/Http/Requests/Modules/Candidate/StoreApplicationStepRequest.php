<?php

namespace App\Http\Requests\Modules\Candidate;

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

        if ($step === 3) {
            return [
                'payload' => ['required', 'array'],
                'payload.linha_pesquisa' => ['required', 'string', Rule::in(ResearchLineCatalog::lineKeys())],
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

            if (! ResearchLineCatalog::isValidAdvisor($linhaPesquisa, $orientador)) {
                $validator->errors()->add(
                    'payload.orientador',
                    'O orientador selecionado não é válido para a linha de pesquisa escolhida.',
                );
            }
        });
    }
}
