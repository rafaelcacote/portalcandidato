<?php

namespace App\Http\Requests\Modules\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSelectionProcessEditalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'edital' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'edital.required' => 'Selecione um arquivo PDF do edital.',
            'edital.file' => 'O edital deve ser um arquivo válido.',
            'edital.mimes' => 'O edital deve estar no formato PDF.',
            'edital.max' => 'O arquivo do edital não pode ultrapassar 20 MB.',
            'edital.uploaded' => 'O arquivo do edital não pode ultrapassar 20 MB.',
        ];
    }
}
