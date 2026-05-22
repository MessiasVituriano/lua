<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BanhoTosaServico;
use Illuminate\Http\Request;

class BanhoTosaServicoController extends Controller
{
    public function index(Request $request)
    {
        $lojaId = auth()->user()->loja_id;

        $query = BanhoTosaServico::query()->where('loja_id', $lojaId);

        if ($request->filled('busca')) {
            $query->where('nome', 'ilike', '%' . $request->busca . '%');
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('ativo')) {
            $query->where('ativo', $request->ativo === '1');
        }

        return response()->json($query->orderBy('categoria')->orderBy('nome')->get());
    }

    public function show(BanhoTosaServico $servico)
    {
        abort_unless($servico->loja_id === auth()->user()->loja_id, 403);
        return response()->json($servico);
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome'            => ['required', 'string', 'max:120'],
            'categoria'       => ['required', 'in:banho,tosa,pacote,extra'],
            'preco_base'      => ['required', 'numeric', 'min:0'],
            'custo_estimado'  => ['nullable', 'numeric', 'min:0'],
            'duracao_minutos' => ['required', 'integer', 'min:5', 'max:480'],
            'descricao'       => ['nullable', 'string', 'max:1000'],
            'ativo'           => ['nullable', 'boolean'],
        ]);

        $servico = BanhoTosaServico::create(array_merge($dados, [
            'loja_id' => auth()->user()->loja_id,
        ]));

        return response()->json($servico, 201);
    }

    public function update(Request $request, BanhoTosaServico $servico)
    {
        abort_unless($servico->loja_id === auth()->user()->loja_id, 403);

        $dados = $request->validate([
            'nome'            => ['sometimes', 'string', 'max:120'],
            'categoria'       => ['sometimes', 'in:banho,tosa,pacote,extra'],
            'preco_base'      => ['sometimes', 'numeric', 'min:0'],
            'custo_estimado'  => ['nullable', 'numeric', 'min:0'],
            'duracao_minutos' => ['sometimes', 'integer', 'min:5', 'max:480'],
            'descricao'       => ['nullable', 'string', 'max:1000'],
            'ativo'           => ['nullable', 'boolean'],
        ]);

        $servico->update($dados);

        return response()->json($servico);
    }

    public function destroy(BanhoTosaServico $servico)
    {
        abort_unless($servico->loja_id === auth()->user()->loja_id, 403);
        $servico->delete();
        return response()->json(null, 204);
    }
}
