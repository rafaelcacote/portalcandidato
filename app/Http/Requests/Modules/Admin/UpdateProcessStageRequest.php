<?php

namespace App\Http\Requests\Modules\Admin;

use App\Http\Requests\Concerns\ParsesDatetimeLocalInAppTimezone;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProcessStageRequest extends FormRequest
{
    use ParsesDatetimeLocalInAppTimezone;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->parseDatetimeLocalFields([
            'inicio_em',
            'fim_em',
            'recurso_inicio_em',
            'recurso_fim_em',
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'ordem' => ['sometimes', 'required', 'integer', 'min:1', 'max:999'],
            'inicio_em' => ['nullable', 'date'],
            'fim_em' => ['nullable', 'date', 'after_or_equal:inicio_em'],
            'recurso_inicio_em' => ['nullable', 'date'],
            'recurso_fim_em' => ['nullable', 'date', 'after_or_equal:recurso_inicio_em'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fim_em.after_or_equal' => 'A data de fim deve ser igual ou posterior à data de início.',
            'recurso_fim_em.after_or_equal' => 'O fim do prazo de recurso deve ser igual ou posterior ao início.',
        ];
    }
}
