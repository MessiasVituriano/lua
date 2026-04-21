<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BandeiraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $lojaId = auth()->user()->loja_id;
        $bandeiraId = $this->route('bandeira')?->id;

        return [
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('bandeiras', 'nome')
                    ->where(fn ($q) => $q->where('loja_id', $lojaId))
                    ->ignore($bandeiraId),
            ],
            'ativo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.unique' => 'Ja existe uma bandeira com este nome nesta loja.',
        ];
    }
}
