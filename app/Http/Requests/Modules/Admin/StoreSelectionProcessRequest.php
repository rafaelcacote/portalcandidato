<?php

namespace App\Http\Requests\Modules\Admin;

use Illuminate\Foundation\Http\FormRequest;

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
            'inscricao_inicio_em' => ['nullable', 'date'],
            'inscricao_fim_em' => ['nullable', 'date', 'after_or_equal:inscricao_inicio_em'],
        ];
    }
}
