<?php

namespace App\Models\Modules\Candidate\Models;

use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Evaluator\Models\ApplicationEvaluation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    protected $fillable = [
        'user_id',
        'selection_process_id',
        'numero_protocolo',
        'status',
        'dados_inscricao',
        'finalizada_em',
    ];

    protected function casts(): array
    {
        return [
            'dados_inscricao' => 'array',
            'finalizada_em' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function selectionProcess(): BelongsTo
    {
        return $this->belongsTo(SelectionProcess::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(ApplicationEvaluation::class);
    }
}
