<?php

namespace App\Http\Requests\Modules\Evaluator;

use App\Models\Modules\Candidate\Models\Application;
use App\Models\Modules\Candidate\Models\ApplicationDocument;
use App\Modules\Evaluator\Services\TitlePeriodQuantityCalculator;
use Illuminate\Foundation\Http\FormRequest;

class DecideApplicationDocumentRequest extends FormRequest
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:aprovado,recusado,pendente'],
            'motivo_recusa' => ['required_if:status,recusado', 'nullable', 'string', 'max:1000'],
            'quantidade' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
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

                if ($this->filled('data_inicio') || $this->filled('data_fim')) {
                    if (! $document instanceof ApplicationDocument || $document->process_title_item_id === null) {
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

                        return;
                    }

                    if (! $this->filled('data_inicio') || ! $this->filled('data_fim')) {
                        $validator->errors()->add(
                            'data_fim',
                            'Informe data inicial e data final do período.',
                        );
                    }

                    return;
                }

                if (! $this->filled('quantidade')) {
                    return;
                }

                if (! $document instanceof ApplicationDocument || $document->process_title_item_id === null) {
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
