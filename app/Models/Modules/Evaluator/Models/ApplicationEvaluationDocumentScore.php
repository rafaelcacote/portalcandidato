<?php

namespace App\Models\Modules\Evaluator\Models;

use App\Models\Modules\Candidate\Models\ApplicationDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationEvaluationDocumentScore extends Model
{
    protected $fillable = [
        'application_evaluation_id',
        'application_document_id',
        'pontuacao',
    ];

    protected function casts(): array
    {
        return [
            'pontuacao' => 'decimal:2',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(ApplicationEvaluation::class, 'application_evaluation_id');
    }

    public function applicationDocument(): BelongsTo
    {
        return $this->belongsTo(ApplicationDocument::class, 'application_document_id');
    }
}
