<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Dados mínimos do perfil cadastral exigidos para inscrição em processos seletivos.
     */
    public function completeCandidateProfile(): static
    {
        return $this->state(fn (array $attributes) => [
            'cpf' => '529.982.247-25',
            'telefone' => '(11) 98765-4321',
            'data_nascimento' => '1990-05-10',
            'foto_path' => 'private/profiles/placeholder.jpg',
            'identidade' => '123456789',
            'orgao_emissor' => 'SSP',
            'identidade_uf' => 'SP',
            'identidade_data_emissao' => '2015-01-20',
            'naturalidade' => 'São Paulo',
            'nacionalidade' => 'Brasileira',
            'sexo' => 'Feminino',
            'endereco' => 'Rua Teste',
            'endereco_numero' => '100',
            'bairro' => 'Centro',
            'cep' => '01001-000',
            'cidade' => 'São Paulo',
            'endereco_uf' => 'SP',
            'pais' => 'Brasil',
        ]);
    }
}
