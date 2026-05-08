<?php

namespace App\Models\Modules\Admin\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessEvaluatorAssignment extends Model
{
    protected $fillable = [
        'selection_process_id',
        'user_id',
        'pode_avaliar',
        'pode_visualizar_resultados',
        'pode_baixar_documentos',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pode_avaliar' => 'boolean',
            'pode_visualizar_resultados' => 'boolean',
            'pode_baixar_documentos' => 'boolean',
        ];
    }

    public function selectionProcess(): BelongsTo
    {
        return $this->belongsTo(SelectionProcess::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
