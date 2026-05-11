<?php

namespace App\Modules\Admin\Services;

use App\Models\Modules\Admin\Models\ProcessRequiredDocument;
use App\Models\Modules\Admin\Models\SelectionProcess;
use App\Models\Modules\Admin\Models\TipoDocumento;
use App\Modules\Shared\Enums\SelectionProcessProgramType;

class SelectionProcessDocumentTemplateService
{
    /**
     * Códigos estáveis dos tipos de documento usados no checklist padrão (Mestrado/Doutorado).
     *
     * @var array<string, string>
     */
    private const DOCUMENT_CATALOG = [
        'comprovante_pagamento' => 'Comprovante de pagamento',
        'comprovante_isencao_taxa' => 'Comprovante de isenção de taxa',
        'declaracao_pcd_anexo_ii' => 'Declaração de Pessoa com Deficiência (Anexo II do edital)',
        'laudo_pcd_ou_carteira' => 'Laudo médico ou parecer multiprofissional / Carteira PcD',
        'documento_identidade_rg_cnh' => 'Carteira de Identidade (RG) ou CNH',
        'diploma_graduacao_enfermagem' => 'Diploma de graduação em Enfermagem',
        'diploma_mestrado_enfermagem' => 'Diploma de mestrado em Enfermagem',
        'certidao_regularidade_coren' => 'Certidão de regularidade junto ao COREN',
        'declaracao_vinculo_enfermeiro' => 'Declaração de vínculo como profissional enfermeiro',
        'pre_projeto_pesquisa' => 'Pré-projeto de pesquisa',
        'curriculo_lattes' => 'Currículo Lattes atualizado',
        'autodeclaracao_cota_anexo_i' => 'Autodeclaração de participante de vaga de cota (Anexo I)',
    ];

    /**
     * @return list<string>
     */
    private function orderedCodesFor(SelectionProcessProgramType $type): array
    {
        $diplomaCodigo = $type === SelectionProcessProgramType::Mestrado
            ? 'diploma_graduacao_enfermagem'
            : 'diploma_mestrado_enfermagem';

        return [
            'comprovante_pagamento',
            'comprovante_isencao_taxa',
            'declaracao_pcd_anexo_ii',
            'laudo_pcd_ou_carteira',
            'documento_identidade_rg_cnh',
            $diplomaCodigo,
            'certidao_regularidade_coren',
            'declaracao_vinculo_enfermeiro',
            'pre_projeto_pesquisa',
            'curriculo_lattes',
            'autodeclaracao_cota_anexo_i',
        ];
    }

    public function syncTemplateDocuments(SelectionProcess $process): void
    {
        if ($process->tipo_programa === null) {
            return;
        }

        $this->ensureCatalogTipos();

        ProcessRequiredDocument::query()
            ->where('selection_process_id', $process->id)
            ->where('gerado_por_template', true)
            ->delete();

        foreach ($this->orderedCodesFor($process->tipo_programa) as $codigo) {
            $tipo = TipoDocumento::query()->where('codigo', $codigo)->firstOrFail();

            $process->requiredDocuments()->create([
                'tipo_documento_id' => $tipo->id,
                'nome' => $tipo->descricao,
                'descricao' => null,
                'formatos_aceitos' => ['pdf', 'jpg', 'png'],
                'tamanho_max_mb' => 10,
                'obrigatorio' => $codigo !== 'comprovante_isencao_taxa',
                'gerado_por_template' => true,
            ]);
        }
    }

    public function shouldResyncTemplateDocuments(SelectionProcess $process, ?SelectionProcessProgramType $previousTipo): bool
    {
        if ($process->tipo_programa === null) {
            return false;
        }

        return $previousTipo !== $process->tipo_programa;
    }

    private function ensureCatalogTipos(): void
    {
        foreach (self::DOCUMENT_CATALOG as $codigo => $descricao) {
            TipoDocumento::query()->updateOrCreate(
                ['codigo' => $codigo],
                ['descricao' => $descricao, 'status' => true],
            );
        }
    }
}
