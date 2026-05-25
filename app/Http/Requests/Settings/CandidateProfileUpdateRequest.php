<?php

namespace App\Http\Requests\Settings;

use App\Concerns\CandidateProfileValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class CandidateProfileUpdateRequest extends FormRequest
{
    use CandidateProfileValidationRules, ProfileValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(
            $this->profileRules($this->user()->id),
            $this->candidateProfileRules(),
        );
    }

    protected function prepareForValidation(): void
    {
        $cepRaw = isset($this->cep) ? preg_replace('/\D/', '', (string) $this->cep) : null;
        $email = isset($this->email) ? Str::lower(trim((string) $this->email)) : null;

        $this->merge(array_filter([
            'cep' => $cepRaw,
            'email' => $email,
        ], fn ($value) => $value !== null));
    }
}
