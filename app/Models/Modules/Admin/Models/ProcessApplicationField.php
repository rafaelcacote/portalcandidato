<?php

namespace App\Models\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessApplicationField extends Model
{
    protected $fillable = [
        'selection_process_id',
        'label',
        'field_key',
        'tipo',
        'obrigatorio',
        'opcoes',
        'ordem',
    ];

    protected function casts(): array
    {
        return [
            'obrigatorio' => 'boolean',
            'opcoes' => 'array',
        ];
    }

    public function selectionProcess(): BelongsTo
    {
        return $this->belongsTo(SelectionProcess::class);
    }
}
