<?php

namespace App\Http\Requests\Modules\Candidate;

use App\Models\Modules\Admin\Models\ProcessRequiredDocument;
use App\Models\Modules\Admin\Models\ProcessTitleItem;
use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Modules\Candidate\Enums\CandidaturaSpecialDocumentKind;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreApplicationDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $application = $this->route('application');

        if (! $application instanceof Application) {
            return false;
        }

        return $this->user()?->id === $application->user_id
            && $application->canModifyDocuments();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'candidatura_document_kind' => [
                'nullable',
                'string',
                Rule::in(CandidaturaSpecialDocumentKind::values()),
                'prohibits:process_required_document_id,process_title_item_id',
            ],
            'process_required_document_id' => [
                'nullable',
                'integer',
                'exists:process_required_documents,id',
                'required_without_all:candidatura_document_kind,process_title_item_id',
                'prohibits:candidatura_document_kind,process_title_item_id',
            ],
            'process_title_item_id' => [
                'nullable',
                'integer',
                'exists:process_title_items,id',
                'required_without_all:candidatura_document_kind,process_required_document_id',
                'prohibits:candidatura_document_kind,process_required_document_id',
            ],
            'arquivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $application = $this->route('application');
            if (! $application instanceof Application) {
                return;
            }

            if ($this->filled('candidatura_document_kind')) {
                $step1 = $application->dados_inscricao['step_1'] ?? [];
                if (($step1['concorre_vagas_pcd'] ?? false) !== true) {
                    $validator->errors()->add(
                        'candidatura_document_kind',
                        'Salve nesta etapa que você concorre às vagas PcD antes de enviar estes documentos.',
                    );
                }
            }

            if ($this->filled('process_required_document_id')) {
                $requiredId = $this->integer('process_required_document_id');
                $belongs = ProcessRequiredDocument::query()
                    ->where('id', $requiredId)
                    ->where('selection_process_id', $application->selection_process_id)
                    ->exists();
                if (! $belongs) {
                    $validator->errors()->add(
                        'process_required_document_id',
                        'Documento inválido para este processo.',
                    );
                }
            }

            if ($this->filled('process_title_item_id')) {
                $titleItemId = $this->integer('process_title_item_id');
                $titleItem = ProcessTitleItem::query()
                    ->with('titleGroup')
                    ->find($titleItemId);

                if ($titleItem === null
                    || $titleItem->titleGroup === null
                    || $titleItem->titleGroup->selection_process_id !== $application->selection_process_id
                ) {
                    $validator->errors()->add(
                        'process_title_item_id',
                        'Item de título inválido para este processo.',
                    );

                    return;
                }

                if (! $titleItem->is_active || ! $titleItem->titleGroup->is_active) {
                    $validator->errors()->add(
                        'process_title_item_id',
                        'Este item de título não está disponível para envio.',
                    );

                    return;
                }

                $maxFileSizeKb = (int) $titleItem->max_file_size_mb * 1024;
                $file = $this->file('arquivo');
                if ($file !== null && $maxFileSizeKb > 0 && ($file->getSize() / 1024) > $maxFileSizeKb) {
                    $validator->errors()->add(
                        'arquivo',
                        "O arquivo excede o tamanho máximo permitido ({$titleItem->max_file_size_mb} MB) para este item.",
                    );
                }

                $accepted = is_array($titleItem->accepted_formats) ? $titleItem->accepted_formats : null;
                if ($file !== null && $accepted !== null && $accepted !== []) {
                    $extension = strtolower($file->getClientOriginalExtension());
                    $allowed = array_map('strtolower', $accepted);
                    if (! in_array($extension, $allowed, true)) {
                        $allowedLabel = strtoupper(implode(', ', $allowed));
                        $validator->errors()->add(
                            'arquivo',
                            "Formato não aceito para este item. Permitidos: {$allowedLabel}.",
                        );
                    }
                }

                if ($titleItem->max_quantity !== null) {
                    $alreadyUploaded = ApplicationDocument::query()
                        ->where('application_id', $application->id)
                        ->where('process_title_item_id', $titleItem->id)
                        ->count();

                    if (($alreadyUploaded + 1) > $titleItem->max_quantity) {
                        $validator->errors()->add(
                            'process_title_item_id',
                            "Você atingiu o limite de {$titleItem->max_quantity} comprovante(s) para este item.",
                        );
                    }
                }
            }
        });
    }
}
