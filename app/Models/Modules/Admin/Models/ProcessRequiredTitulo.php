<?php

namespace App\Models\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessRequiredTitulo extends Model
{
    protected $table = 'process_required_titulos';

    protected $fillable = [
        'selection_process_id',
        'tipo_titulo_id',
        'pontuacao_max',
        'qtd_maxima',
        'obrigatorio',
        'formatos_aceitos',
        'tamanho_max_mb',
        'descricao',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pontuacao_max' => 'decimal:2',
            'obrigatorio' => 'boolean',
            'formatos_aceitos' => 'array',
        ];
    }

    public function selectionProcess(): BelongsTo
    {
        return $this->belongsTo(SelectionProcess::class);
    }

    public function tipoTitulo(): BelongsTo
    {
        return $this->belongsTo(TipoTitulo::class);
    }
}
