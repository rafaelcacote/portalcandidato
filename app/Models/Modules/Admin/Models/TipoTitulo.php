<?php

namespace App\Models\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoTitulo extends Model
{
    protected $fillable = [
        'descricao',
        'status',
        'calculo',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function processRequiredDocuments(): HasMany
    {
        return $this->hasMany(ProcessRequiredDocument::class);
    }
}
