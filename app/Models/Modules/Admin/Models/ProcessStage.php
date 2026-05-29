<?php

namespace App\Models\Modules\Admin\Models;

use App\Models\Modules\Candidate\Models\ApplicationAppeal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessStage extends Model
{
    protected $fillable = [
        'selection_process_id',
        'ordem',
        'nome',
        'inicio_em',
        'fim_em',
        'recurso_inicio_em',
        'recurso_fim_em',
    ];

    protected function casts(): array
    {
        return [
            'inicio_em' => 'datetime',
            'fim_em' => 'datetime',
            'recurso_inicio_em' => 'datetime',
            'recurso_fim_em' => 'datetime',
        ];
    }

    public function appeals(): HasMany
    {
        return $this->hasMany(ApplicationAppeal::class);
    }

    public function selectionProcess(): BelongsTo
    {
        return $this->belongsTo(SelectionProcess::class);
    }
}
