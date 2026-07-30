<?php

namespace App\Http\Requests\Modules\Evaluator;

use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationDocumentQuantidadeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $application = $this->route('application');

        return $application instanceof Application
            && $application->isEvaluable();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'quantidade' => ['required', 'integer', 'min:1', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quantidade.required' => 'Informe a quantidade (anos, semestres ou unidades).',
            'quantidade.integer' => 'A quantidade deve ser um número inteiro.',
            'quantidade.min' => 'A quantidade mínima é 1.',
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                /** @var ApplicationDocument|null $document */
                $document = $this->route('applicationDocument');

                if (! $document instanceof ApplicationDocument) {
                    $validator->errors()->add('quantidade', 'Documento inválido.');

                    return;
                }

                if ($document->process_title_item_id === null) {
                    $validator->errors()->add(
                        'quantidade',
                        'A quantidade só se aplica a comprovantes de titulação.',
                    );

                    return;
                }

                $document->loadMissing('titleItem');
                $maxQuantity = $document->titleItem?->max_quantity;
                $quantidade = (int) $this->input('quantidade');

                if ($maxQuantity !== null && $quantidade > (int) $maxQuantity) {
                    $validator->errors()->add(
                        'quantidade',
                        "A quantidade não pode ultrapassar {$maxQuantity} conforme o edital.",
                    );
                }
            },
        ];
    }
}
