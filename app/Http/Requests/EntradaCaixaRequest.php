<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EntradaCaixaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'forma_recebimento' => ['required', Rule::in(['dinheiro', 'pix', 'cartao_debito', 'cartao_credito'])],
            'banco_id' => ['nullable', 'exists:bancos,id'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'descricao' => ['nullable', 'string', 'max:255'],
        ];
    }
}
