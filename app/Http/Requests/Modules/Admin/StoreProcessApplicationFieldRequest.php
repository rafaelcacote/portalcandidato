<?php

namespace App\Http\Requests\Modules\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProcessApplicationFieldRequest extends FormRequest
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
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'field_key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'tipo' => ['required', Rule::in(['text', 'textarea', 'number', 'date', 'select'])],
            'obrigatorio' => ['required', 'boolean'],
            'opcoes' => ['nullable', 'string'],
            'ordem' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }
}
