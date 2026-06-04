<?php

namespace App\Models\Modules\Admin\Models;

use App\Models\Modules\Candidate\Models\Application;
use App\Modules\Shared\Enums\SelectionProcessProgramType;
use App\Modules\Shared\Enums\SelectionProcessStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
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
     * Process is active and the enrollment window (when configured) includes now.
     */
    public function inscricaoEstaAberta(?CarbonInterface $now = null): bool
    {
        if ($this->status !== SelectionProcessStatus::Ativo->value) {
            return false;
        }

        $now ??= now();

        if ($this->inscricao_inicio_em !== null && $now->lt($this->inscricao_inicio_em)) {
            return false;
        }

        if ($this->inscricao_fim_em !== null && $now->gt($this->inscricao_fim_em)) {
            return false;
        }

        return true;
    }

    /**
     * @param  Builder<SelectionProcess>  $query
     */
    public function scopeInscricaoAberta(Builder $query, ?CarbonInterface $now = null): void
    {
        $now ??= now();

        $query
            ->where('status', SelectionProcessStatus::Ativo->value)
            ->where(function (Builder $inner) use ($now): void {
                $inner
                    ->whereNull('inscricao_inicio_em')
                    ->orWhere('inscricao_inicio_em', '<=', $now);
            })
            ->where(function (Builder $inner) use ($now): void {
                $inner
                    ->whereNull('inscricao_fim_em')
                    ->orWhere('inscricao_fim_em', '>=', $now);
            });
    }

    /**
     * Inscrição ainda aberta hoje e data de encerramento cai no dia civil daqui a N dias.
     *
     * @param  Builder<SelectionProcess>  $query
     */
    public function scopeInscricaoEncerraEmDias(Builder $query, int $days, ?CarbonInterface $now = null): void
    {
        $now = ($now ?? now())->timezone(config('app.timezone'));
        $windowStart = $now->copy()->addDays($days)->startOfDay();
        $windowEnd = $now->copy()->addDays($days)->endOfDay();

        $query
            ->inscricaoAberta($now)
            ->whereNotNull('inscricao_fim_em')
            ->whereBetween('inscricao_fim_em', [$windowStart, $windowEnd]);
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
