<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProdutoRequest;
use App\Models\MovimentacaoEstoque;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        if ($request->filled('sem_fornecedor')) {
            $query->whereNull('fornecedor_id');
        }

        if ($request->filled('ativo')) {
            $query->where('ativo', $request->ativo === '1');
        }

        if ($request->filled('estoque_baixo')) {
            $query->whereNotNull('estoque_min')
                ->whereColumn('estoque_atual', '<=', 'estoque_min');
        }

        return response()->json($query->orderBy('nome')->paginate((int) $request->input('per_page', 20)));
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

    /**
     * Recebimento de mercadoria: lanca a entrada de varios produtos de uma vez.
     * Ou entra tudo, ou nada — uma nota nunca fica pela metade no estoque.
     */
    public function registrarMovimentacaoLote(Request $request)
    {
        $request->validate([
            'tipo' => ['nullable', 'in:entrada,saida'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.produto_id' => ['required', 'integer'],
            'itens.*.quantidade' => ['required', 'integer', 'min:1'],
        ]);

        $tipo = $request->input('tipo', 'entrada');
        $lojaId = auth()->user()->loja_id;

        // Soma quantidades repetidas do mesmo produto — a tela pode mandar a mesma linha duas vezes.
        $itens = collect($request->itens)
            ->groupBy('produto_id')
            ->map(fn ($linhas) => (int) $linhas->sum('quantidade'));

        return DB::transaction(function () use ($itens, $tipo, $request, $lojaId) {
            $produtos = Produto::where('loja_id', $lojaId)
                ->whereIn('id', $itens->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $faltando = $itens->keys()->reject(fn ($id) => $produtos->has($id));
            if ($faltando->isNotEmpty()) {
                abort(422, 'Produto nao encontrado nesta loja: ' . $faltando->implode(', '));
            }

            if ($tipo === 'saida') {
                $insuficientes = $itens
                    ->filter(fn ($qtd, $id) => $produtos[$id]->estoque_atual < $qtd)
                    ->map(fn ($qtd, $id) => $produtos[$id]->nome);

                if ($insuficientes->isNotEmpty()) {
                    abort(422, 'Estoque insuficiente: ' . $insuficientes->implode(', '));
                }
            }

            foreach ($itens as $produtoId => $quantidade) {
                MovimentacaoEstoque::create([
                    'produto_id' => $produtoId,
                    'tipo' => $tipo,
                    'quantidade' => $quantidade,
                    'motivo' => $request->motivo,
                    'usuario_id' => auth()->id(),
                ]);

                $produto = $produtos[$produtoId];
                $tipo === 'entrada'
                    ? $produto->increment('estoque_atual', $quantidade)
                    : $produto->decrement('estoque_atual', $quantidade);
            }

            return response()->json([
                'total' => $itens->count(),
                'produtos' => Produto::with('fornecedor')->whereIn('id', $itens->keys())->get(),
            ]);
        });
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
