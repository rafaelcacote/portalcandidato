<?php

namespace App\Models\Modules\Admin\Models;

use App\Models\Modules\Candidate\Models\ApplicationDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessRequiredDocument extends Model
{
    protected $fillable = [
        'selection_process_id',
        'tipo_documento_id',
        'nome',
        'descricao',
        'formatos_aceitos',
        'tamanho_max_mb',
        'obrigatorio',
        'gerado_por_template',
    ];

    protected function casts(): array
    {
        return [
            'formatos_aceitos' => 'array',
            'obrigatorio' => 'boolean',
            'gerado_por_template' => 'boolean',
        ];
    }

    public function selectionProcess(): BelongsTo
    {
        return $this->belongsTo(SelectionProcess::class);
    }

    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    public function applicationDocuments(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }
}
