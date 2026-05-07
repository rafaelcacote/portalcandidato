<?php

namespace App\Models\Modules\Evaluator\Models;

use App\Models\Modules\Admin\Models\ProcessCriteria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationEvaluationScore extends Model
{
    protected $fillable = [
        'application_evaluation_id',
        'process_criteria_id',
        'pontuacao',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(ApplicationEvaluation::class, 'application_evaluation_id');
    }

    public function criteria(): BelongsTo
    {
        return $this->belongsTo(ProcessCriteria::class, 'process_criteria_id');
    }
}
