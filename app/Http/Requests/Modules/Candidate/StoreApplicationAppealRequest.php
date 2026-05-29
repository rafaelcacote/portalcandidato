<?php

namespace App\Http\Requests\Modules\Candidate;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationAppealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'process_stage_id' => ['required', 'integer', 'exists:process_stages,id'],
            'texto' => ['required', 'string', 'min:20', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'process_stage_id.required' => 'Selecione a etapa do processo para o recurso.',
            'texto.required' => 'Descreva o motivo do recurso.',
            'texto.min' => 'O texto do recurso deve ter pelo menos 20 caracteres.',
            'texto.max' => 'O texto do recurso não pode ultrapassar 5.000 caracteres.',
        ];
    }
}
