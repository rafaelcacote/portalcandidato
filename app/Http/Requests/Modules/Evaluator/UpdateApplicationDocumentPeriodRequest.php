<?php

namespace App\Http\Requests\Modules\Evaluator;

use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Modules\Evaluator\Services\TitlePeriodQuantityCalculator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationDocumentPeriodRequest extends FormRequest
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
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'data_inicio.required' => 'Informe a data inicial do período.',
            'data_fim.required' => 'Informe a data final do período.',
            'data_fim.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
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
                $calculator = resolve(TitlePeriodQuantityCalculator::class);

                if (! $document instanceof ApplicationDocument) {
                    $validator->errors()->add('data_inicio', 'Documento inválido.');

                    return;
                }

                if ($document->process_title_item_id === null) {
                    $validator->errors()->add(
                        'data_inicio',
                        'O período só se aplica a comprovantes de titulação.',
                    );

                    return;
                }

                $document->loadMissing('titleItem');

                if (! $calculator->usesPeriodDates($document->titleItem)) {
                    $validator->errors()->add(
                        'data_inicio',
                        'Este título não utiliza cálculo por período (data início/fim).',
                    );
                }
            },
        ];
    }
}
