<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FornecedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'categoria' => ['required', Rule::in(['racao', 'medicamento', 'acessorio', 'higiene', 'outros'])],
            'telefone' => ['nullable', 'string', 'max:20'],
            'ativo' => ['boolean'],
        ];
    }
}
