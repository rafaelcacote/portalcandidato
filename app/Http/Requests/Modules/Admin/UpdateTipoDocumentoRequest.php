<?php

namespace App\Http\Requests\Modules\Admin;

use App\Models\Modules\Admin\Models\TipoDocumento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTipoDocumentoRequest extends FormRequest
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
        /** @var TipoDocumento $tipoDocumento */
        $tipoDocumento = $this->route('tipoDocumento');

        return [
            'codigo' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('tipo_documentos', 'codigo')->ignore($tipoDocumento),
            ],
            'descricao' => ['required', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ];
    }
}
