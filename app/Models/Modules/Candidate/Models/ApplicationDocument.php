<?php

namespace App\Models\Modules\Candidate\Models;

use App\Models\Modules\Admin\Models\ProcessRequiredDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDocument extends Model
{
    protected $fillable = [
        'application_id',
        'process_required_document_id',
        'caminho',
        'nome_arquivo',
        'mime',
        'versao',
        'status',
        'motivo_recusa',
        'validado_por',
        'validado_em',
    ];

    protected function casts(): array
    {
        return [
            'validado_em' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function requiredDocument(): BelongsTo
    {
        return $this->belongsTo(ProcessRequiredDocument::class, 'process_required_document_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por');
    }
}
