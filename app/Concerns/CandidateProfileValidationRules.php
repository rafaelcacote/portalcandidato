<?php

namespace App\Concerns;

use App\Support\BrazilianStates;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait CandidateProfileValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function candidateProfileRules(): array
    {
        $ufs = BrazilianStates::abbreviations();

        return [
            'data_nascimento' => ['required', 'date', 'before:today'],
            'identidade' => ['required', 'string', 'max:32'],
            'orgao_emissor' => ['required', 'string', 'max:50'],
            'identidade_uf' => ['required', 'string', 'size:2', Rule::in($ufs)],
            'identidade_data_emissao' => ['required', 'date', 'before_or_equal:today'],
            'naturalidade' => ['required', 'string', 'max:120'],
            'nacionalidade' => ['required', 'string', 'max:120'],
            'sexo' => ['required', 'string', Rule::in(['masculino', 'feminino', 'outro', 'prefiro_nao_informar'])],
            'endereco' => ['required', 'string', 'max:255'],
            'endereco_numero' => ['required', 'string', 'max:20'],
            'bairro' => ['required', 'string', 'max:120'],
            'cep' => ['required', 'string', 'regex:/^\d{8}$/'],
            'cidade' => ['required', 'string', 'max:120'],
            'endereco_uf' => ['required', 'string', 'size:2', Rule::in($ufs)],
            'pais' => ['required', 'string', 'max:120'],
            'telefone' => ['required', 'string', 'max:25'],
            'telefone_fixo' => ['nullable', 'string', 'max:25'],
            'foto' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
