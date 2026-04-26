<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fornecedor_id' => ['nullable', 'exists:fornecedores,id'],
            'nome' => ['required', 'string', 'max:255'],
            'categoria' => ['required', Rule::in(['racao', 'medicamento', 'acessorio', 'higiene', 'petisco'])],
            'valor_custo' => ['required', 'numeric', 'min:0.01'],
            'margem' => ['required', 'numeric', 'min:0'],
            'estoque_min' => ['nullable', 'integer', 'min:0'],
            'ativo' => ['boolean'],
        ];
    }
}
