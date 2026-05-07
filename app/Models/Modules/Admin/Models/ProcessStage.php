<?php

namespace App\Models\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessStage extends Model
{
    protected $fillable = [
        'selection_process_id',
        'ordem',
        'nome',
        'inicio_em',
        'fim_em',
    ];

    protected function casts(): array
    {
        return [
            'inicio_em' => 'datetime',
            'fim_em' => 'datetime',
        ];
    }

    public function selectionProcess(): BelongsTo
    {
        return $this->belongsTo(SelectionProcess::class);
    }
}
