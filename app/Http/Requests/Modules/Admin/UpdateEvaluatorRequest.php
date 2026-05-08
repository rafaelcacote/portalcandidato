<?php

namespace App\Http\Requests\Modules\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;

class UpdateEvaluatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|ValidationRule|Password|Unique>>
     */
    public function rules(): array
    {
        $userId = $this->route('evaluator')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'cpf' => [
                'nullable', 'string', 'max:14',
                Rule::unique('users', 'cpf')->ignore($userId),
            ],
            'telefone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', Password::min(8)],
            'ativo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Este campo é obrigatório.',
            'email.required' => 'Este campo é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Já existe um usuário com este e-mail.',
            'cpf.unique' => 'Já existe um usuário com este CPF.',
            'password.min' => 'A senha deve ter no mínimo :min caracteres.',
        ];
    }
}
