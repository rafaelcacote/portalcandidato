<?php

namespace App\Http\Requests\Modules\Admin;

use App\Modules\Shared\Enums\SelectionProcessProgramType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSelectionProcessRequest extends FormRequest
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['required', 'string'],
            'regras' => ['nullable', 'string'],
            'status' => ['required', 'in:rascunho,ativo,encerrado'],
            'tipo_programa' => ['required', Rule::enum(SelectionProcessProgramType::class)],
            'inscricao_inicio_em' => ['nullable', 'date'],
            'inscricao_fim_em' => ['nullable', 'date', 'after_or_equal:inscricao_inicio_em'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'titulo.required' => 'Este campo é obrigatório.',
            'titulo.max' => 'O título não pode ter mais de :max caracteres.',
            'descricao.required' => 'Este campo é obrigatório.',
            'status.required' => 'Este campo é obrigatório.',
            'status.in' => 'O status selecionado é inválido.',
            'inscricao_inicio_em.date' => 'Informe uma data de início válida.',
            'inscricao_fim_em.date' => 'Informe uma data de fim válida.',
            'inscricao_fim_em.after_or_equal' => 'A data de fim das inscrições deve ser igual ou posterior à data de início.',
        ];
    }
}
