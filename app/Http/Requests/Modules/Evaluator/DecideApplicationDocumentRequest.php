<?php

namespace App\Http\Requests\Modules\Evaluator;

use Illuminate\Foundation\Http\FormRequest;

class DecideApplicationDocumentRequest extends FormRequest
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
            'status' => ['required', 'in:aprovado,recusado,pendente'],
            'motivo_recusa' => ['required_if:status,recusado', 'nullable', 'string', 'max:1000'],
        ];
    }
}
