<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProdutoRequest;
use App\Models\MovimentacaoEstoque;
use App\Models\Produto;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{
    public function index(Request $request)
    {
        $lojaId = auth()->user()->loja_id;

        $query = Produto::with('fornecedor')->where('loja_id', $lojaId);

        if ($request->filled('busca')) {
            $query->where('nome', 'ilike', '%' . $request->busca . '%');
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('fornecedor_id')) {
            $query->where('fornecedor_id', $request->fornecedor_id);
        }

        if ($request->filled('estoque_baixo')) {
            $query->whereNotNull('estoque_min')
                ->whereColumn('estoque_atual', '<=', 'estoque_min');
        }

        return response()->json($query->orderBy('nome')->paginate(20));
    }

    public function store(ProdutoRequest $request)
    {
        $data = $request->validated();
        $data['loja_id'] = auth()->user()->loja_id;
        $data['valor_venda'] = Produto::calcularValorVenda($data['valor_custo'], $data['margem']);

        $produto = Produto::create($data);

        return response()->json($produto->load('fornecedor'), 201);
    }

    public function show(Produto $produto)
    {
        return response()->json($produto->load('fornecedor'));
    }

    public function update(ProdutoRequest $request, Produto $produto)
    {
        $data = $request->validated();
        $data['valor_venda'] = Produto::calcularValorVenda($data['valor_custo'], $data['margem']);

        $produto->update($data);

        return response()->json($produto->load('fornecedor'));
    }

    public function destroy(Produto $produto)
    {
        $produto->delete();
        return response()->json(null, 204);
    }

    public function movimentacoes(Produto $produto)
    {
        $movs = $produto->movimentacoes()
            ->with('usuario')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($movs);
    }

    public function registrarMovimentacao(Request $request, Produto $produto)
    {
        $request->validate([
            'tipo' => ['required', 'in:entrada,saida'],
            'quantidade' => ['required', 'integer', 'min:1'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->tipo === 'saida' && $produto->estoque_atual < $request->quantidade) {
            return response()->json(['message' => 'Estoque insuficiente.'], 422);
        }

        MovimentacaoEstoque::create([
            'produto_id' => $produto->id,
            'tipo' => $request->tipo,
            'quantidade' => $request->quantidade,
            'motivo' => $request->motivo,
            'usuario_id' => auth()->id(),
        ]);

        if ($request->tipo === 'entrada') {
            $produto->increment('estoque_atual', $request->quantidade);
        } else {
            $produto->decrement('estoque_atual', $request->quantidade);
        }

        return response()->json($produto->fresh()->load('fornecedor'));
    }
}
