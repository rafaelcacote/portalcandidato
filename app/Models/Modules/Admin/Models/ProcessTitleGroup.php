<?php

namespace App\Models\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessTitleGroup extends Model
{
    protected $fillable = [
        'selection_process_id',
        'code',
        'name',
        'description',
        'max_score',
        'order',
        'is_active',
        'generated_by_template',
    ];

    protected function casts(): array
    {
        return [
            'max_score' => 'decimal:2',
            'is_active' => 'boolean',
            'generated_by_template' => 'boolean',
        ];
    }

    public function selectionProcess(): BelongsTo
    {
        return $this->belongsTo(SelectionProcess::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcessTitleItem::class)->orderBy('order')->orderBy('code');
    }
}
