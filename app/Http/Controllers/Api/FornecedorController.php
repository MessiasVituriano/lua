<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FornecedorRequest;
use App\Models\Fornecedor;
use App\Models\Produto;
use Illuminate\Http\Request;

class FornecedorController extends Controller
{
    public function index(Request $request)
    {
        $lojaId = auth()->user()->loja_id;

        $query = Fornecedor::query()
            ->withCount(['produtos' => fn ($q) => $q->where('loja_id', $lojaId)]);

        if ($request->filled('busca')) {
            $query->where('nome', 'ilike', '%' . $request->busca . '%');
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('ativo')) {
            $query->where('ativo', $request->ativo === '1');
        }

        // Perspectiva "fornecedores": mantem quem tem ao menos um produto atendendo aos filtros
        // de produto. Um unico whereHas para os dois criterios — assim a linha listada nunca
        // expande vazia, porque a sublista usa exatamente os mesmos filtros.
        if ($request->filled('busca_produto') || $request->filled('estoque_baixo')) {
            $query->whereHas('produtos', function ($q) use ($request, $lojaId) {
                $q->where('loja_id', $lojaId);

                if ($request->filled('busca_produto')) {
                    $q->where('nome', 'ilike', '%' . $request->busca_produto . '%');
                }

                if ($request->filled('estoque_baixo')) {
                    $q->whereNotNull('estoque_min')
                        ->whereColumn('estoque_atual', '<=', 'estoque_min');
                }
            });
        }

        return response()->json($query->orderBy('nome')->paginate((int) $request->input('per_page', 15)));
    }

    /**
     * Produtos da loja atual vinculados ao fornecedor — usado no accordion da tela de Estoque.
     */
    public function produtos(Request $request, Fornecedor $fornecedor)
    {
        $query = $fornecedor->produtos()
            ->where('loja_id', auth()->user()->loja_id);

        if ($request->filled('busca')) {
            $query->where('nome', 'ilike', '%' . $request->busca . '%');
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('estoque_baixo')) {
            $query->whereNotNull('estoque_min')
                ->whereColumn('estoque_atual', '<=', 'estoque_min');
        }

        return response()->json(['data' => $query->orderBy('nome')->get()]);
    }

    /**
     * Produtos da loja sem fornecedor vinculado — linha "Sem fornecedor" da mesma tela.
     */
    public function produtosSemFornecedor(Request $request)
    {
        $query = Produto::whereNull('fornecedor_id')
            ->where('loja_id', auth()->user()->loja_id);

        if ($request->filled('busca')) {
            $query->where('nome', 'ilike', '%' . $request->busca . '%');
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('estoque_baixo')) {
            $query->whereNotNull('estoque_min')
                ->whereColumn('estoque_atual', '<=', 'estoque_min');
        }

        return response()->json(['data' => $query->orderBy('nome')->get()]);
    }

    public function store(FornecedorRequest $request)
    {
        $fornecedor = Fornecedor::create($request->validated());
        return response()->json($fornecedor, 201);
    }

    public function show(Fornecedor $fornecedor)
    {
        return response()->json($fornecedor);
    }

    public function update(FornecedorRequest $request, Fornecedor $fornecedor)
    {
        $fornecedor->update($request->validated());
        return response()->json($fornecedor);
    }

    public function destroy(Fornecedor $fornecedor)
    {
        $fornecedor->delete();
        return response()->json(null, 204);
    }
}
