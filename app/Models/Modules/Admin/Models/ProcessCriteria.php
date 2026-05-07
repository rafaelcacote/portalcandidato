<?php

namespace App\Models\Modules\Admin\Models;

use App\Models\Modules\Evaluator\Models\ApplicationEvaluationScore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessCriteria extends Model
{
    protected $table = 'process_criteria';

    protected $fillable = [
        'selection_process_id',
        'nome',
        'peso',
        'pontuacao_max',
        'ordem',
    ];

    public function selectionProcess(): BelongsTo
    {
        return $this->belongsTo(SelectionProcess::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(ApplicationEvaluationScore::class);
    }
}
