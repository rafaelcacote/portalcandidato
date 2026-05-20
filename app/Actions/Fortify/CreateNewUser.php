<?php

namespace App\Actions\Fortify;

use App\Concerns\CandidateRegistrationValidationRules;
use App\Models\User;
use App\Rules\Cpf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Spatie\Permission\Models\Role;

class CreateNewUser implements CreatesNewUsers
{
    use CandidateRegistrationValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        $input = $this->prepareRegistrationInput($input);

        Validator::make($input, $this->candidateRegistrationRules(), [
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'email.unique' => 'Este e-mail já está cadastrado.',
        ])->validate();

        /** @var UploadedFile $foto */
        $foto = $input['foto'];

        $attributes = Arr::only($input, [
            'name',
            'email',
            'password',
            'data_nascimento',
            'cpf',
            'telefone',
            'identidade',
            'orgao_emissor',
            'identidade_uf',
            'identidade_data_emissao',
            'naturalidade',
            'nacionalidade',
            'sexo',
            'endereco',
            'endereco_numero',
            'bairro',
            'cep',
            'cidade',
            'endereco_uf',
            'pais',
            'telefone_fixo',
        ]);

        $user = User::create($attributes);

        Role::query()->firstOrCreate([
            'name' => 'candidato',
            'guard_name' => 'web',
        ]);

        $user->assignRole('candidato');

        $path = $foto->store('candidate-photos/'.$user->id, 'public');
        $user->forceFill(['foto_path' => $path])->save();

        return $user;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function prepareRegistrationInput(array $input): array
    {
        $cpf = Cpf::normalizeToDigits($input['cpf'] ?? '');
        $cepRaw = isset($input['cep']) ? preg_replace('/\D/', '', (string) $input['cep']) : '';

        $email = isset($input['email']) ? Str::lower(trim((string) $input['email'])) : null;

        return array_merge($input, [
            'cpf' => $cpf,
            'cep' => $cepRaw,
            'email' => $email,
        ]);
    }
}
