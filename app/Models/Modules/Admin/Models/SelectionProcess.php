<?php

namespace App\Models\Modules\Admin\Models;

use App\Models\Modules\Candidate\Models\Application;
use App\Modules\Shared\Enums\SelectionProcessProgramType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SelectionProcess extends Model
{
    /**
     * @var list<string>
     */
    protected $appends = [
        'edital_download_url',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'edital_pdf_path',
    ];

    protected $fillable = [
        'titulo',
        'descricao',
        'regras',
        'status',
        'tipo_programa',
        'edital_pdf_path',
        'inscricao_inicio_em',
        'inscricao_fim_em',
    ];

    protected function casts(): array
    {
        return [
            'tipo_programa' => SelectionProcessProgramType::class,
            'inscricao_inicio_em' => 'datetime',
            'inscricao_fim_em' => 'datetime',
        ];
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ProcessStage::class)->orderBy('ordem');
    }

    public function requiredDocuments(): HasMany
    {
        return $this->hasMany(ProcessRequiredDocument::class);
    }

    public function requiredTitulos(): HasMany
    {
        return $this->hasMany(ProcessRequiredTitulo::class);
    }

    public function titleGroups(): HasMany
    {
        return $this->hasMany(ProcessTitleGroup::class)->orderBy('order')->orderBy('code');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(ProcessCriteria::class);
    }

    public function evaluatorAssignments(): HasMany
    {
        return $this->hasMany(ProcessEvaluatorAssignment::class);
    }

    public function applicationFields(): HasMany
    {
        return $this->hasMany(ProcessApplicationField::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Eager-load definitions for title groups and items shown to candidates (active records only).
     *
     * @return array<string, callable(mixed): void>
     */
    public static function candidateTitleCatalogEagerLoads(): array
    {
        return [
            'titleGroups' => function ($query): void {
                $query->where('is_active', true)
                    ->orderBy('order')
                    ->orderBy('code')
                    ->with([
                        'items' => function ($q): void {
                            $q->where('is_active', true)
                                ->orderBy('order')
                                ->orderBy('code');
                        },
                    ]);
            },
        ];
    }

    /**
     * URL to download the official notice (edital) PDF for authenticated users.
     */
    protected function editalDownloadUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->edital_pdf_path === null) {
                return null;
            }

            return route('selection-processes.edital.show', $this);
        });
    }
}
