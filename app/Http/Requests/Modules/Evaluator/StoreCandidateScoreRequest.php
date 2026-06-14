<?php

namespace App\Http\Requests\Modules\Evaluator;

use App\Models\Modules\Admin\Models\ProcessCriteria;
use App\Models\Modules\Admin\Models\ProcessTitleGroup;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Modules\Evaluator\Services\TitleDocumentScoring;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;

class StoreCandidateScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('application');

        return $application instanceof Application
            && $application->isEvaluable();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'scores' => ['present', 'array'],
            'scores.*.process_criteria_id' => ['required', 'integer', 'exists:process_criteria,id'],
            'scores.*.pontuacao' => ['required', 'numeric', 'min:0'],
            'document_scores' => ['present', 'array'],
            'document_scores.*.application_document_id' => ['required', 'integer', 'exists:application_documents,id'],
            'document_scores.*.pontuacao' => ['required', 'numeric', 'min:0'],
            'resultado' => ['nullable', 'in:apto,inapto,classificado,desclassificado,suplente'],
            'observacoes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            /** @var Application|null $application */
            $application = $this->route('application');
            if (! $application instanceof Application) {
                return;
            }

            $this->validateCriteriaCaps($v, $application);
            $this->validateTitleDocumentScores($v, $application);
        });
    }

    private function validateCriteriaCaps(Validator $v, Application $application): void
    {
        $selectionProcessId = $application->selection_process_id;
        $rows = $this->input('scores', []);
        if ($rows === []) {
            return;
        }

        $criteriaIds = collect($rows)->pluck('process_criteria_id')->unique()->values();
        $criteriaById = ProcessCriteria::query()
            ->where('selection_process_id', $selectionProcessId)
            ->whereIn('id', $criteriaIds)
            ->get(['id', 'pontuacao_max'])
            ->keyBy('id');

        foreach ($rows as $index => $row) {
            $cid = (int) ($row['process_criteria_id'] ?? 0);
            $criteria = $criteriaById->get($cid);
            if ($criteria === null) {
                $v->errors()->add("scores.{$index}.process_criteria_id", 'Este critério não pertence ao processo seletivo da inscrição.');

                continue;
            }
            $max = (float) $criteria->pontuacao_max;
            $value = (float) ($row['pontuacao'] ?? 0);
            if ($value > $max + 0.0001) {
                $v->errors()->add("scores.{$index}.pontuacao", "A pontuação não pode ultrapassar {$max} neste critério.");
            }
        }
    }

    private function validateTitleDocumentScores(Validator $v, Application $application): void
    {
        $rows = $this->input('document_scores', []);
        if ($rows === []) {
            return;
        }

        $ids = collect($rows)->pluck('application_document_id')->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return;
        }

        /** @var Collection<int, ApplicationDocument> $documents */
        $documents = ApplicationDocument::query()
            ->where('application_id', $application->id)
            ->whereIn('id', $ids)
            ->with(['titleItem.titleGroup'])
            ->get()
            ->keyBy('id');

        $groupTotals = [];

        foreach ($rows as $index => $row) {
            $docId = (int) ($row['application_document_id'] ?? 0);
            $doc = $documents->get($docId);
            if ($doc === null) {
                $v->errors()->add("document_scores.{$index}.application_document_id", 'Documento inválido para esta inscrição.');

                continue;
            }

            if ($doc->process_title_item_id === null || (int) $doc->process_title_item_id === 0) {
                $v->errors()->add("document_scores.{$index}.application_document_id", 'A pontuação por documento só se aplica a itens de formação acadêmica / titulação.');

                continue;
            }

            $item = $doc->titleItem;
            if ($item === null || $item->titleGroup === null) {
                $v->errors()->add("document_scores.{$index}.application_document_id", 'Metadados de titulação incompletos para este documento.');

                continue;
            }

            $rowMax = resolve(TitleDocumentScoring::class)->maxPointsForRow($doc);
            $value = (float) ($row['pontuacao'] ?? 0);
            if ($value > $rowMax + 0.0001) {
                $v->errors()->add(
                    "document_scores.{$index}.pontuacao",
                    "A pontuação deste item não pode ultrapassar {$rowMax} (conforme regra do edital).",
                );
            }

            $gid = (int) $item->process_title_group_id;
            $groupTotals[$gid] = ($groupTotals[$gid] ?? 0.0) + $value;
        }

        $groupIds = array_keys($groupTotals);
        if ($groupIds === []) {
            return;
        }

        $groupCaps = ProcessTitleGroup::query()
            ->whereIn('id', $groupIds)
            ->where('selection_process_id', $application->selection_process_id)
            ->pluck('max_score', 'id');

        foreach ($groupTotals as $groupId => $sum) {
            $cap = $groupCaps->get((int) $groupId);
            if ($cap === null) {
                continue;
            }
            $capFloat = (float) $cap;
            if ($sum > $capFloat + 0.0001) {
                $v->errors()->add(
                    'document_scores',
                    "A soma da pontuação no grupo de titulação (ID {$groupId}) não pode ultrapassar {$capFloat}. Total informado: ".round($sum, 2).'.',
                );
            }
        }
    }
}
