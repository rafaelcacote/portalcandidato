<?php

namespace App\Models\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoDocumento extends Model
{
    protected $fillable = [
        'descricao',
        'status',
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
