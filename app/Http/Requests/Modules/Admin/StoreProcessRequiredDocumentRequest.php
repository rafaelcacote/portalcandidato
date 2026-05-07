<?php

namespace App\Http\Requests\Modules\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProcessRequiredDocumentRequest extends FormRequest
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
            'tipo_documento_id' => ['required', 'integer', 'exists:tipo_documentos,id'],
            'tipo_titulo_id' => ['required', 'integer', 'exists:tipo_titulos,id'],
            'descricao' => ['nullable', 'string'],
            'formatos_aceitos' => ['nullable', 'string', 'max:255'],
            'tamanho_max_mb' => ['required', 'integer', 'min:1', 'max:100'],
            'obrigatorio' => ['required', 'boolean'],
        ];
    }
}
