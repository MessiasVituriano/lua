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
        $ehCartao = in_array($this->input('forma_recebimento'), ['cartao_debito', 'cartao_credito']);
        $ehCredito = $this->input('forma_recebimento') === 'cartao_credito';

        return [
            'forma_recebimento' => ['required', Rule::in(['dinheiro', 'pix', 'cartao_debito', 'cartao_credito'])],
            'banco_id' => ['nullable', 'exists:bancos,id'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'bandeira_id' => [$ehCartao ? 'required' : 'nullable', 'integer', 'exists:bandeiras,id'],
            'parcelas' => [
                $ehCredito ? 'required' : 'nullable',
                'integer',
                'min:1',
                'max:12',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'bandeira_id.required' => 'Informe a bandeira do cartao.',
            'parcelas.required' => 'Informe a quantidade de parcelas.',
        ];
    }
}
