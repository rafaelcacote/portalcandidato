<?php

namespace App\Http\Requests\Modules\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProcessRequiredTituloRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $selectionProcessId = $this->route('selectionProcess')?->id;

        return [
            'tipo_titulo_id' => [
                'required', 'integer', 'exists:tipo_titulos,id',
                Rule::unique('process_required_titulos', 'tipo_titulo_id')
                    ->where(fn ($query) => $query->where('selection_process_id', $selectionProcessId)),
            ],
            'pontuacao_max' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'qtd_maxima' => ['nullable', 'integer', 'min:1', 'max:999'],
            'obrigatorio' => ['required', 'boolean'],
            'formatos_aceitos' => ['nullable', 'string', 'max:255'],
            'tamanho_max_mb' => ['required', 'integer', 'min:1', 'max:100'],
            'descricao' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo_titulo_id.required' => 'Selecione um tipo de título.',
            'tipo_titulo_id.unique' => 'Este tipo de título já está vinculado ao processo.',
            'pontuacao_max.required' => 'Informe a pontuação máxima.',
        ];
    }
}
