<?php

namespace App\Http\Requests\Modules\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProcessCriteriaRequest extends FormRequest
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
            'nome' => ['required', 'string', 'max:255'],
            'peso' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'pontuacao_max' => ['required', 'numeric', 'min:1', 'max:1000'],
            'ordem' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }
}
