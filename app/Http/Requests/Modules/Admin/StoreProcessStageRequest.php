<?php

namespace App\Http\Requests\Modules\Admin;

use App\Http\Requests\Concerns\ParsesDatetimeLocalInAppTimezone;
use Illuminate\Foundation\Http\FormRequest;

class StoreProcessStageRequest extends FormRequest
{
    use ParsesDatetimeLocalInAppTimezone;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->parseDatetimeLocalFields([
            'inicio_em',
            'fim_em',
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'ordem' => ['required', 'integer', 'min:1', 'max:999'],
            'inicio_em' => ['nullable', 'date'],
            'fim_em' => ['nullable', 'date', 'after_or_equal:inicio_em'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fim_em.after_or_equal' => 'A data de fim deve ser igual ou posterior à data de início.',
        ];
    }
}
