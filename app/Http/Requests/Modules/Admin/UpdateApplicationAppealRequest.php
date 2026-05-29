<?php

namespace App\Http\Requests\Modules\Admin;

use App\Modules\Shared\Enums\ApplicationAppealStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationAppealRequest extends FormRequest
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
        $requiresResponse = in_array($this->input('status'), [
            ApplicationAppealStatus::Deferido->value,
            ApplicationAppealStatus::Indeferido->value,
        ], true);

        return [
            'status' => ['required', 'string', Rule::in(ApplicationAppealStatus::values())],
            'resposta' => [
                $requiresResponse ? 'required' : 'nullable',
                'string',
                'min:10',
                'max:5000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Informe o status da análise do recurso.',
            'status.in' => 'Status de recurso inválido.',
            'resposta.required' => 'Informe a resposta ao candidato ao deferir ou indeferir o recurso.',
            'resposta.min' => 'A resposta deve ter pelo menos 10 caracteres.',
        ];
    }
}
