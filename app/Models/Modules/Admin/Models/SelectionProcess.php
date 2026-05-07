<?php

namespace App\Models\Modules\Admin\Models;

use App\Models\Modules\Candidate\Models\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SelectionProcess extends Model
{
    protected $fillable = [
        'titulo',
        'descricao',
        'regras',
        'status',
        'inscricao_inicio_em',
        'inscricao_fim_em',
    ];

    protected function casts(): array
    {
        return [
            'inscricao_inicio_em' => 'datetime',
            'inscricao_fim_em' => 'datetime',
        ];
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ProcessStage::class);
    }

    public function requiredDocuments(): HasMany
    {
        return $this->hasMany(ProcessRequiredDocument::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(ProcessCriteria::class);
    }

    public function evaluatorAssignments(): HasMany
    {
        return $this->hasMany(ProcessEvaluatorAssignment::class);
    }

    public function applicationFields(): HasMany
    {
        return $this->hasMany(ProcessApplicationField::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
