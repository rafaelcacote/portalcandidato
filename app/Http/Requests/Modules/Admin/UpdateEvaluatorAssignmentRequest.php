<?php

namespace App\Http\Requests\Modules\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEvaluatorAssignmentRequest extends FormRequest
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
            'pode_avaliar' => ['required', 'boolean'],
            'pode_visualizar_resultados' => ['required', 'boolean'],
            'pode_baixar_documentos' => ['required', 'boolean'],
        ];
    }
}
