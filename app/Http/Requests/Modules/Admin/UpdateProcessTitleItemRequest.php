<?php

namespace App\Http\Requests\Modules\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProcessTitleItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:2000'],
            'score_per_unit' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'score_unit' => ['required', 'string', 'max:80'],
            'max_quantity' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'period_rule' => ['nullable', 'string', 'max:255'],
            'requires_attachment' => ['boolean'],
            'accepted_formats' => ['nullable', 'string', 'max:255'],
            'max_file_size_mb' => ['integer', 'min:1', 'max:100'],
            'candidate_instructions' => ['nullable', 'string', 'max:2000'],
            'order' => ['integer', 'min:0', 'max:999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Informe o código do item.',
            'title.required' => 'Informe a descrição do título.',
            'score_per_unit.required' => 'Informe a pontuação por unidade.',
            'score_per_unit.numeric' => 'A pontuação deve ser um número.',
            'score_unit.required' => 'Informe a unidade de pontuação.',
        ];
    }
}
