<?php

namespace App\Http\Requests;

use App\Models\EntradaCaixaItem;
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
            'valor' => ['nullable', 'required_without:itens', 'numeric', 'min:0.01'],
            'desconto' => ['nullable', 'numeric', 'min:0'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'bandeira_id' => [$ehCartao ? 'required' : 'nullable', 'integer', 'exists:bandeiras,id'],
            'parcelas' => [
                $ehCredito ? 'required' : 'nullable',
                'integer',
                'min:1',
                'max:12',
            ],
            'itens' => ['nullable', 'array', 'min:1'],
            'itens.*.produto_id' => ['nullable', 'exists:produtos,id'],
            'itens.*.quantidade' => ['required_with:itens', 'numeric', 'min:0.001'],
            'itens.*.preco_unitario' => ['nullable', 'numeric', 'min:0'],
            'itens.*.subtotal' => ['nullable', 'numeric', 'min:0'],
            'itens.*.peso_gramas' => ['nullable', 'integer', 'min:1'],
            'itens.*.perfil_pet_tipo' => ['nullable', Rule::in(EntradaCaixaItem::PERFIS_PET)],
            'itens.*.cliente_id' => ['nullable', 'integer', 'min:1', 'exists:clientes,id'],
            'itens.*.pet_id' => ['nullable', 'integer', 'min:1', 'exists:pets,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'bandeira_id.required' => 'Informe a bandeira do cartao.',
            'parcelas.required' => 'Informe a quantidade de parcelas.',
            'valor.required_without' => 'Informe o valor da entrada ou adicione pelo menos um item.',
        ];
    }
}
