<?php

namespace App\Http\Requests\Modules\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProcessRequiredDocumentRequest extends FormRequest
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
        return [
            'descricao' => ['nullable', 'string'],
            'formatos_aceitos' => ['nullable', 'string', 'max:255'],
            'tamanho_max_mb' => ['required', 'integer', 'min:1', 'max:100'],
            'obrigatorio' => ['required', 'boolean'],
        ];
    }
}
