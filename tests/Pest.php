<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * @return array<string, mixed>
 */
function validCandidateRegistrationPayload(string $email): array
{
    return [
        'name' => 'Candidato Teste',
        'email' => $email,
        'email_confirmation' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
        'data_nascimento' => '1995-06-15',
        'cpf' => '529.982.247-25',
        'identidade' => '12.345.678-9',
        'orgao_emissor' => 'SSP',
        'identidade_uf' => 'SP',
        'identidade_data_emissao' => '2018-03-20',
        'naturalidade' => 'São Paulo',
        'nacionalidade' => 'Brasileira',
        'sexo' => 'feminino',
        'endereco' => 'Rua das Flores',
        'endereco_numero' => '100',
        'bairro' => 'Centro',
        'cep' => '01310-100',
        'cidade' => 'São Paulo',
        'endereco_uf' => 'SP',
        'pais' => 'Brasil',
        'telefone' => '(11) 98888-7777',
        'telefone_fixo' => '',
        'foto' => UploadedFile::fake()->image('avatar.jpg', 100, 100),
        'lgpd_consent' => true,
    ];
}
