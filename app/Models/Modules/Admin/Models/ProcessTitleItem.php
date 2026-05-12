<?php

namespace App\Models\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessTitleItem extends Model
{
    protected $fillable = [
        'process_title_group_id',
        'code',
        'title',
        'score_per_unit',
        'score_unit',
        'max_quantity',
        'period_rule',
        'requires_attachment',
        'accepted_formats',
        'max_file_size_mb',
        'candidate_instructions',
        'is_active',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'score_per_unit' => 'decimal:2',
            'requires_attachment' => 'boolean',
            'accepted_formats' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function titleGroup(): BelongsTo
    {
        return $this->belongsTo(ProcessTitleGroup::class, 'process_title_group_id');
    }
}
