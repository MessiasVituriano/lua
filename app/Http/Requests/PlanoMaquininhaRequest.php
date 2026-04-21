<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanoMaquininhaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'taxa_antecipacao' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ativo' => ['boolean'],
            'taxas' => ['sometimes', 'array'],
            'taxas.*.bandeira_id' => ['required_with:taxas', 'integer', 'exists:bandeiras,id'],
            'taxas.*.modalidade' => ['required_with:taxas', Rule::in(['debito', 'credito_avista', 'credito_2_6', 'credito_7_12'])],
            'taxas.*.taxa' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
