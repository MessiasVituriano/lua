<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MovimentacaoInternaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(['transferencia_banco', 'sangria', 'aporte', 'transferencia_loja'])],
            'banco_origem_id' => ['nullable', 'exists:bancos,id'],
            'banco_destino_id' => ['nullable', 'exists:bancos,id'],
            'loja_destino_id' => ['nullable', 'required_if:tipo,transferencia_loja', 'exists:lojas,id'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'descricao' => ['required', 'string', 'max:255'],
            'data_movimentacao' => ['required', 'date'],
            'observacao' => ['nullable', 'string'],
        ];
    }
}
