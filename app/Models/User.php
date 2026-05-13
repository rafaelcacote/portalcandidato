<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Modules\Admin\Models\ProcessEvaluatorAssignment;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Evaluator\Models\ApplicationEvaluation;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'cpf',
    'telefone',
    'telefone_fixo',
    'data_nascimento',
    'ativo',
    'password',
    'foto_path',
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
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * @var list<string>
     */
    protected $appends = [
        'foto_url',
    ];

    /**
     * Public URL for the candidate profile photo when stored on the public disk.
     */
    protected function fotoUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            $path = $this->foto_path;
            if ($path === null || trim((string) $path) === '') {
                return null;
            }

            $path = (string) $path;
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }

            if (str_starts_with($path, 'private/')) {
                return null;
            }

            return Storage::disk('public')->url(ltrim($path, '/'));
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'data_nascimento' => 'date',
            'identidade_data_emissao' => 'date',
            'ativo' => 'boolean',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function evaluatorAssignments(): HasMany
    {
        return $this->hasMany(ProcessEvaluatorAssignment::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(ApplicationEvaluation::class, 'evaluator_id');
    }

    public function candidateProfileIsComplete(): bool
    {
        if (! $this->hasRole('candidato')) {
            return true;
        }

        $stringFields = [
            'name',
            'email',
            'cpf',
            'telefone',
            'foto_path',
            'identidade',
            'orgao_emissor',
            'identidade_uf',
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
        ];

        foreach ($stringFields as $field) {
            $value = $this->getAttribute($field);
            if ($value === null || trim((string) $value) === '') {
                return false;
            }
        }

        return $this->data_nascimento !== null
            && $this->identidade_data_emissao !== null;
    }
}
