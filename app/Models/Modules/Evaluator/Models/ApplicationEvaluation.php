<?php

namespace App\Models\Modules\Evaluator\Models;

use App\Models\Modules\Candidate\Models\Application;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationEvaluation extends Model
{
    protected $fillable = [
        'application_id',
        'evaluator_id',
        'status',
        'resultado',
        'observacoes',
        'pontuacao_total',
        'concluida_em',
    ];

    protected function casts(): array
    {
        return [
            'concluida_em' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(ApplicationEvaluationScore::class);
    }
}
