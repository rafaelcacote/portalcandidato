<?php

namespace App\Models\Modules\Candidate\Models;

use App\Models\Modules\Admin\Models\ProcessStage;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationAppeal extends Model
{
    protected $fillable = [
        'application_id',
        'process_stage_id',
        'texto',
        'status',
        'resposta',
        'respondido_por',
        'respondido_em',
    ];

    protected function casts(): array
    {
        return [
            'respondido_em' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function processStage(): BelongsTo
    {
        return $this->belongsTo(ProcessStage::class);
    }

    public function respondidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'respondido_por');
    }
}
