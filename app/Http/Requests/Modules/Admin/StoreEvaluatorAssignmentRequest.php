<?php

namespace App\Http\Requests\Modules\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluatorAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $userId = $this->route('evaluator') instanceof User
            ? $this->route('evaluator')->id
            : $this->input('user_id');

        return [
            'selection_process_id' => [
                'required', 'integer', 'exists:selection_processes,id',
                Rule::unique('process_evaluator_assignments', 'selection_process_id')
                    ->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'pode_avaliar' => ['required', 'boolean'],
            'pode_visualizar_resultados' => ['required', 'boolean'],
            'pode_baixar_documentos' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'selection_process_id.required' => 'Selecione um processo.',
            'selection_process_id.unique' => 'Este avaliador já está vinculado ao processo selecionado.',
        ];
    }
}
