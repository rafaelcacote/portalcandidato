<?php

namespace App\Http\Requests\Modules\Admin;

use App\Models\User;
use App\Rules\Cpf;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;

class UpdateEvaluatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $evaluator = $this->route('evaluator');

        if ($evaluator instanceof User) {
            abort_unless($evaluator->hasRole('avaliador'), 404);
        }

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
                'required', 'string', 'size:11', new Cpf,
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
            'cpf.required' => 'Este campo é obrigatório.',
            'cpf.size' => 'Informe um CPF válido.',
            'cpf.unique' => 'Já existe um usuário com este CPF.',
            'password.min' => 'A senha deve ter no mínimo :min caracteres.',
        ];
    }
}
