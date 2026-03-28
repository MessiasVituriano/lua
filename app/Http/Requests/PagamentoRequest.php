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
        ];
    }
}
