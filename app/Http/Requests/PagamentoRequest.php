<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PagamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fornecedor_id' => ['nullable', 'exists:fornecedores,id'],
            'categoria' => ['required', Rule::in(['boleto', 'imposto', 'custo_fixo', 'funcionario', 'fornecedor', 'outros'])],
            'descricao' => ['required', 'string', 'max:255'],
            'valor_total' => ['required', 'numeric', 'min:0.01'],
            'data_vencimento' => ['required', 'date'],
            'observacao' => ['nullable', 'string'],
            'recorrente' => ['boolean'],
            'dia_recorrencia' => ['nullable', 'required_if:recorrente,true', 'integer', 'min:1', 'max:31'],
            'quantidade_parcelas' => ['nullable', 'integer', 'min:1', 'max:60'],
            'recorrencia_dias' => ['nullable', 'integer', 'min:1', 'max:365'],
            'data_primeiro_pagamento' => ['nullable', 'date'],
            'parcelas_lote' => ['nullable', 'array', 'min:1'],
            'parcelas_lote.*.numero' => ['nullable', 'integer', 'min:1'],
            'parcelas_lote.*.data_vencimento' => ['required_with:parcelas_lote', 'date'],
            'parcelas_lote.*.valor_total' => ['required_with:parcelas_lote', 'numeric', 'min:0.01'],
        ];
    }
}
