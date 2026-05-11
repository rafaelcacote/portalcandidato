<?php

namespace App\Http\Requests\Modules\Admin;

use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreEvaluatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $cpf = $this->input('cpf');

        if ($cpf === null) {
            return;
        }

        $trimmed = trim((string) $cpf);

        $this->merge([
            'cpf' => $trimmed === '' ? '' : Cpf::normalizeToDigits($trimmed),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'cpf' => ['required', 'string', 'size:11', new Cpf, 'unique:users,cpf'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', Password::min(8)],
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
            'cpf.required' => 'Este campo é obrigatório.',
            'cpf.size' => 'Informe um CPF válido.',
            'cpf.unique' => 'Já existe um usuário com este CPF.',
            'password.required' => 'Este campo é obrigatório.',
            'password.min' => 'A senha deve ter no mínimo :min caracteres.',
        ];
    }
}
