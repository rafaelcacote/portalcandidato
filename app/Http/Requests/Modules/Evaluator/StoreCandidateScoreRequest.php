<?php

namespace App\Http\Requests\Modules\Evaluator;

use Illuminate\Foundation\Http\FormRequest;

class StoreCandidateScoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'scores' => ['required', 'array'],
            'scores.*.process_criteria_id' => ['required', 'integer', 'exists:process_criteria,id'],
            'scores.*.pontuacao' => ['required', 'numeric', 'min:0'],
            'resultado' => ['nullable', 'in:apto,inapto,classificado,desclassificado,suplente'],
            'observacoes' => ['nullable', 'string'],
        ];
    }
}
