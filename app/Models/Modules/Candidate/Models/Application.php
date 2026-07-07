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
        'enrollment_deadline_reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'dados_inscricao' => 'array',
            'finalizada_em' => 'datetime',
            'enrollment_deadline_reminder_sent_at' => 'datetime',
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

    public function appeals(): HasMany
    {
        return $this->hasMany(ApplicationAppeal::class);
    }

    public function isFinalizedForDocuments(): bool
    {
        return $this->finalizada_em !== null
            && $this->numero_protocolo !== null
            && $this->status !== 'rascunho';
    }

    public function canModifyEnrollment(): bool
    {
        $this->loadMissing('selectionProcess');

        $selectionProcess = $this->selectionProcess;

        if ($selectionProcess === null) {
            return false;
        }

        return $selectionProcess->inscricaoEstaAberta();
    }

    public function canModifyDocuments(): bool
    {
        return ! $this->isFinalizedForDocuments() && $this->canModifyEnrollment();
    }

    public function isEvaluable(): bool
    {
        return $this->isFinalizedForDocuments();
    }
}
