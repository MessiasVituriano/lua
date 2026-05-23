<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PedidoCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fornecedor_id' => ['required', 'exists:fornecedores,id'],
            'data_estimativa_entrega' => ['required', 'date'],
            'observacao' => ['nullable', 'string'],

            // Dados do pagamento (preenchidos antes de confirmar)
            'data_vencimento' => ['nullable', 'date'],
            'forma_pagamento' => ['nullable', Rule::in(['dinheiro', 'pix', 'boleto', 'transferencia'])],
            'banco_id' => ['nullable', 'exists:bancos,id'],
            'quantidade_parcelas' => ['nullable', 'integer', 'min:1', 'max:60'],
            'recorrencia_dias' => ['nullable', 'integer', 'min:1', 'max:365'],

            // Itens do pedido
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.produto_id' => ['required', 'exists:produtos,id'],
            'itens.*.quantidade' => ['required', 'integer', 'min:1'],
            'itens.*.valor_unitario' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }
}
