<?php

namespace App\Http\Requests\Modules\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluatorAssignmentRequest extends FormRequest
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
            'selection_process_id' => ['required', 'integer', 'exists:selection_processes,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
