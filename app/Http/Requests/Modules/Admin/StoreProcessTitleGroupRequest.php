<?php

namespace App\Http\Requests\Modules\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProcessTitleGroupRequest extends FormRequest
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
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('process_title_groups', 'code')
                    ->where('selection_process_id', $this->route('selectionProcess')?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'max_score' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'order' => ['integer', 'min:0', 'max:999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Informe o código do grupo (ex.: A, B, C).',
            'code.unique' => 'Já existe um grupo com este código neste processo.',
            'name.required' => 'Informe o nome do grupo.',
            'max_score.required' => 'Informe a pontuação máxima do grupo.',
            'max_score.numeric' => 'A pontuação máxima deve ser um número.',
        ];
    }
}
