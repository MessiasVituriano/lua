<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EntradaCaixaRequest;
use App\Models\Bandeira;
use App\Models\CaixaDiario;
use App\Models\Cliente;
use App\Models\EntradaCaixa;
use App\Models\EntradaCaixaItem;
use App\Models\MovimentacaoEstoque;
use App\Models\Pet;
use App\Models\PlanoMaquininha;
use App\Models\Produto;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CaixaController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin')->only(['historico', 'autorizar', 'reabrir']);
    }

    public function hoje()
    {
        $lojaId = auth()->user()->loja_id;
        $hoje = Carbon::today();

        $caixa = CaixaDiario::with(['entradas.banco', 'entradas.bandeira', 'entradas.itens.produto', 'entradas.itens.pet.cliente', 'entradas.itens.cliente', 'fechadoPor'])
            ->where('loja_id', $lojaId)
            ->where('data', $hoje)
            ->first();

        $totaisPorForma = [];
        if ($caixa) {
            $totaisPorForma = $caixa->entradas()
                ->selectRaw('forma_recebimento, SUM(valor) as total')
                ->groupBy('forma_recebimento')
                ->pluck('total', 'forma_recebimento');
        }

        return response()->json([
            'caixa' => $caixa,
            'totais_por_forma' => $totaisPorForma,
        ]);
    }

    public function abrir(Request $request)
    {
        $lojaId = auth()->user()->loja_id;
        $hoje = Carbon::today();

        $data = $hoje;
        if ($request->filled('data')) {
            $data = Carbon::parse($request->input('data'))->startOfDay();

            if ($data->gt($hoje)) {
                return response()->json(['message' => 'Nao e possivel abrir caixa em data futura.'], 422);
            }

            if ($data->lt($hoje) && !auth()->user()->isAdmin()) {
                return response()->json(['message' => 'Apenas administradores podem abrir caixa retroativo.'], 403);
            }
        }

        $existente = CaixaDiario::where('loja_id', $lojaId)->where('data', $data)->first();
        if ($existente) {
            return response()->json($existente);
        }

        $caixa = CaixaDiario::create([
            'loja_id' => $lojaId,
            'data' => $data,
            'status' => 'aberto',
        ]);

        return response()->json($caixa, 201);
    }

    public function adicionarEntrada(EntradaCaixaRequest $request, CaixaDiario $caixa)
    {
        if (in_array($caixa->status, ['fechado', 'pendente'])) {
            return response()->json(['message' => 'Caixa nao esta aberto.'], 422);
        }

        $dados = $request->validated();
        $itensPayload = collect($dados['itens'] ?? []);
        unset($dados['itens']);

        $totalItens = $this->calcularTotalItens($itensPayload);
        $desconto = round((float) ($dados['desconto'] ?? 0), 2);

        $forma = $dados['forma_recebimento'];
        $ehCartao = in_array($forma, ['cartao_debito', 'cartao_credito']);

        $entrada = DB::transaction(function () use ($caixa, $dados, $forma, $ehCartao, $totalItens, $itensPayload, $desconto) {
            if ($ehCartao) {
                $lojaId = $caixa->loja_id;

                $bandeira = Bandeira::where('id', $dados['bandeira_id'])
                    ->where('loja_id', $lojaId)
                    ->where('ativo', true)
                    ->first();

                if (!$bandeira) {
                    throw new HttpResponseException(response()->json(['message' => 'Bandeira invalida para esta loja.'], 422));
                }

                $plano = PlanoMaquininha::where('loja_id', $lojaId)
                    ->where('ativo', true)
                    ->first();

                if (!$plano) {
                    throw new HttpResponseException(response()->json(['message' => 'Nenhum plano de maquininha ativo. Cadastre um plano antes de registrar vendas por cartao.'], 422));
                }

                $parcelas = $forma === 'cartao_credito' ? (int) ($dados['parcelas'] ?? 1) : 1;
                $modalidade = PlanoMaquininha::modalidadePara($forma, $parcelas);

                if (!$modalidade) {
                    throw new HttpResponseException(response()->json(['message' => 'Modalidade invalida.'], 422));
                }

                $valorBase = array_key_exists('valor', $dados) && $dados['valor'] !== null
                    ? (float) $dados['valor']
                    : $totalItens;

                $valorBruto = round($valorBase - $desconto, 2);

                if ($valorBruto <= 0) {
                    throw new HttpResponseException(response()->json(['message' => 'Valor da entrada invalido.'], 422));
                }

                $calc = $plano->calcularLiquido($valorBruto, $bandeira->id, $modalidade, $forma === 'cartao_credito');

                if (!$calc['ok']) {
                    throw new HttpResponseException(response()->json(['message' => $calc['erro']], 422));
                }

                $entrada = $caixa->entradas()->create([
                    'forma_recebimento' => $forma,
                    'banco_id' => $dados['banco_id'] ?? null,
                    'plano_maquininha_id' => $plano->id,
                    'bandeira_id' => $bandeira->id,
                    'parcelas' => $parcelas,
                    'taxa_aplicada' => $calc['taxa_total'],
                    'valor_bruto' => $valorBruto,
                    'com_antecipacao' => $calc['com_antecipacao'],
                    'valor' => $calc['valor_liquido'],
                    'desconto' => $desconto,
                    'descricao' => $dados['descricao'] ?? null,
                ]);
            } else {
                $valorBase = array_key_exists('valor', $dados) && $dados['valor'] !== null
                    ? (float) $dados['valor']
                    : $totalItens;

                $valorEntrada = round($valorBase - $desconto, 2);

                if ($valorEntrada <= 0) {
                    throw new HttpResponseException(response()->json(['message' => 'Valor da entrada invalido.'], 422));
                }

                $entrada = $caixa->entradas()->create([
                    'forma_recebimento' => $forma,
                    'banco_id' => $dados['banco_id'] ?? null,
                    'valor' => $valorEntrada,
                    'desconto' => $desconto,
                    'descricao' => $dados['descricao'] ?? null,
                ]);
            }

            if ($itensPayload->isNotEmpty()) {
                $this->persistirItens($entrada, $itensPayload, $caixa->data);
            }

            $caixa->recalcular();

            return $entrada;
        });

        return response()->json($entrada->load(['banco', 'bandeira', 'planoMaquininha', 'itens.produto', 'itens.pet.cliente', 'itens.cliente']), 201);
    }

    public function removerEntrada(CaixaDiario $caixa, EntradaCaixa $entrada)
    {
        if (in_array($caixa->status, ['fechado', 'pendente'])) {
            return response()->json(['message' => 'Caixa nao esta aberto.'], 422);
        }

        DB::transaction(function () use ($entrada, $caixa) {
            $itens = $entrada->itens()->get(['id', 'produto_id', 'quantidade', 'peso_gramas']);

            if ($itens->isNotEmpty()) {
                $produtoIds = $itens
                    ->pluck('produto_id')
                    ->filter()
                    ->unique()
                    ->values();

                $produtos = Produto::query()
                    ->whereIn('id', $produtoIds)
                    ->get(['id', 'categoria', 'estoque_atual'])
                    ->keyBy('id');

                foreach ($itens as $item) {
                    if (!$item->produto_id) {
                        continue;
                    }

                    $produto = $produtos->get((int) $item->produto_id);
                    if (!$produto) {
                        continue;
                    }

                    $qtdeEstorno = $this->resolverQuantidadeSaidaParaEstoque($produto, [
                        'quantidade' => $item->quantidade,
                        'peso_gramas' => $item->peso_gramas,
                    ]);

                    if ($qtdeEstorno <= 0) {
                        continue;
                    }

                    MovimentacaoEstoque::create([
                        'produto_id' => $produto->id,
                        'tipo' => 'entrada',
                        'quantidade' => $qtdeEstorno,
                        'motivo' => 'Estorno venda caixa #' . $entrada->id,
                        'usuario_id' => auth()->id(),
                    ]);

                    $produto->increment('estoque_atual', $qtdeEstorno);
                }
            }

            $entrada->delete();
            $caixa->recalcular();
        });

        return response()->json(null, 204);
    }

    public function fechar(CaixaDiario $caixa)
    {
        if (in_array($caixa->status, ['fechado', 'pendente'])) {
            return response()->json(['message' => 'Caixa ja foi fechado ou esta pendente.'], 422);
        }

        $caixa->recalcular();
        $user = auth()->user();

        if ($user->isAdmin()) {
            // Admin fecha diretamente
            $caixa->update([
                'status' => 'fechado',
                'fechado_por' => $user->id,
                'fechado_em' => now(),
                'autorizado_por' => $user->id,
                'autorizado_em' => now(),
            ]);
        } else {
            // Atendente coloca em pendente
            $caixa->update([
                'status' => 'pendente',
                'fechado_por' => $user->id,
                'fechado_em' => now(),
            ]);
        }

        return response()->json($caixa->load(['fechadoPor', 'autorizadoPor']));
    }

    public function autorizar(CaixaDiario $caixa)
    {
        if ($caixa->status !== 'pendente') {
            return response()->json(['message' => 'Caixa nao esta pendente de autorizacao.'], 422);
        }

        $caixa->update([
            'status' => 'fechado',
            'autorizado_por' => auth()->id(),
            'autorizado_em' => now(),
        ]);

        return response()->json($caixa->load(['fechadoPor', 'autorizadoPor']));
    }

    public function reabrir(CaixaDiario $caixa)
    {
        if ($caixa->status === 'aberto') {
            return response()->json(['message' => 'Caixa ja esta aberto.'], 422);
        }

        $caixa->update([
            'status' => 'aberto',
            'fechado_por' => null,
            'fechado_em' => null,
            'autorizado_por' => null,
            'autorizado_em' => null,
        ]);

        return response()->json($caixa->fresh()->load(['entradas.banco', 'fechadoPor']));
    }

    public function pendentes()
    {
        $lojaId = auth()->user()->loja_id;

        $pendentes = CaixaDiario::with('fechadoPor')
            ->where('loja_id', $lojaId)
            ->where('status', 'pendente')
            ->orderByDesc('data')
            ->get(['id', 'data', 'total_entradas', 'total_saidas', 'saldo', 'status', 'fechado_por', 'fechado_em']);

        return response()->json($pendentes);
    }

    public function historico(Request $request)
    {
        $lojaId = auth()->user()->loja_id;

        $query = CaixaDiario::with(['fechadoPor', 'autorizadoPor'])
            ->where('loja_id', $lojaId)
            ->orderByDesc('data');

        if ($request->filled('data_inicio')) {
            $query->where('data', '>=', $request->data_inicio);
        }

        if ($request->filled('data_fim')) {
            $query->where('data', '<=', $request->data_fim);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $base = (clone $query);
        $totais = [
            'total_entradas' => (float) (clone $base)->sum('total_entradas'),
            'total_saidas' => (float) (clone $base)->sum('total_saidas'),
            'saldo' => (float) (clone $base)->sum('saldo'),
            'count' => (clone $base)->count(),
        ];

        $paginated = $query->paginate(15)->toArray();
        $paginated['totais'] = $totais;

        return response()->json($paginated);
    }

    public function show(CaixaDiario $caixa)
    {
        $caixa->load(['entradas.banco', 'entradas.bandeira', 'entradas.itens.produto', 'entradas.itens.pet.cliente', 'entradas.itens.cliente', 'fechadoPor']);

        $totaisPorForma = $caixa->entradas()
            ->selectRaw('forma_recebimento, SUM(valor) as total')
            ->groupBy('forma_recebimento')
            ->pluck('total', 'forma_recebimento');

        return response()->json([
            'caixa' => $caixa,
            'totais_por_forma' => $totaisPorForma,
        ]);
    }

    public function produtosRacaoFavoritos()
    {
        $lojaId = auth()->user()->loja_id;

        $produtos = Produto::query()
            ->where('produtos.loja_id', $lojaId)
            ->where('produtos.ativo', true)
            ->leftJoinSub(
                \DB::table('entrada_caixa_itens')
                    ->select('produto_id', \DB::raw('COUNT(*) as total_vendas'))
                    ->groupBy('produto_id'),
                'vendas',
                'vendas.produto_id', '=', 'produtos.id'
            )
            ->orderByRaw('COALESCE(vendas.total_vendas, 0) DESC')
            ->orderBy('produtos.nome')
            ->get(['produtos.id', 'produtos.nome', 'produtos.valor_venda', 'produtos.categoria', \DB::raw('COALESCE(vendas.total_vendas, 0) as total_vendas')]);

        return response()->json($produtos);
    }

    public function clientesComPets()
    {
        $lojaId = auth()->user()->loja_id;

        $clientes = Cliente::query()
            ->where('loja_id', $lojaId)
            ->where('ativo', true)
            ->with(['pets' => function ($query) {
                $query->where('ativo', true)
                    ->orderBy('nome')
                    ->select(['id', 'cliente_id', 'nome', 'tipo', 'porte', 'raca', 'idade_meses']);
            }])
            ->orderBy('nome')
            ->get(['id', 'nome', 'telefone']);

        return response()->json($clientes);
    }

    public function criarClienteComPet(Request $request)
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'pet_nome' => ['required', 'string', 'max:255'],
            'pet_tipo' => ['nullable', 'in:cao,gato,outros'],
            'pet_porte' => ['nullable', 'in:pequeno,medio,grande'],
            'pet_raca' => ['nullable', 'string', 'max:80'],
            'pet_idade_meses' => ['nullable', 'integer', 'min:0', 'max:400'],
        ]);

        $lojaId = auth()->user()->loja_id;

        $cliente = Cliente::create([
            'loja_id' => $lojaId,
            'nome' => $dados['nome'],
            'telefone' => $dados['telefone'] ?? null,
            'ativo' => true,
        ]);

        $pet = Pet::create([
            'cliente_id' => $cliente->id,
            'nome' => $dados['pet_nome'],
            'tipo' => $dados['pet_tipo'] ?? null,
            'porte' => $dados['pet_porte'] ?? null,
            'raca' => $dados['pet_raca'] ?? null,
            'idade_meses' => $dados['pet_idade_meses'] ?? null,
            'ativo' => true,
        ]);

        return response()->json([
            'cliente' => $cliente,
            'pet' => $pet,
        ], 201);
    }

    private function calcularTotalItens($itensPayload): float
    {
        if ($itensPayload->isEmpty()) {
            return 0;
        }

        $total = 0;
        foreach ($itensPayload as $item) {
            $quantidade = (float) ($item['quantidade'] ?? 0);
            $precoUnitario = array_key_exists('preco_unitario', $item) && $item['preco_unitario'] !== null
                ? (float) $item['preco_unitario']
                : null;
            $subtotal = array_key_exists('subtotal', $item) && $item['subtotal'] !== null
                ? (float) $item['subtotal']
                : null;

            if ($subtotal === null && $precoUnitario !== null) {
                $subtotal = $quantidade * $precoUnitario;
            }

            $total += (float) $subtotal;
        }

        return round($total, 2);
    }

    private function persistirItens(EntradaCaixa $entrada, $itensPayload, $dataVenda): void
    {
        $lojaId = auth()->user()->loja_id;

        $produtoIds = $itensPayload
            ->pluck('produto_id')
            ->filter()
            ->unique()
            ->values();

        $petIds = $itensPayload
            ->pluck('pet_id')
            ->filter()
            ->unique()
            ->values();

        $clienteIds = $itensPayload
            ->pluck('cliente_id')
            ->filter()
            ->unique()
            ->values();

        $produtos = Produto::query()
            ->whereIn('id', $produtoIds)
            ->get(['id', 'categoria', 'valor_venda', 'estoque_atual'])
            ->keyBy('id');

        $pets = Pet::query()
            ->whereIn('id', $petIds)
            ->whereHas('cliente', function ($query) use ($lojaId) {
                $query->where('loja_id', $lojaId);
            })
            ->get(['id', 'cliente_id', 'nome', 'tipo', 'porte', 'raca', 'idade_meses'])
            ->keyBy('id');

        $clientes = Cliente::query()
            ->whereIn('id', $clienteIds)
            ->where('loja_id', $lojaId)
            ->get(['id', 'loja_id', 'nome'])
            ->keyBy('id');

        $saidasPorProduto = [];
        foreach ($itensPayload as $item) {
            if (empty($item['produto_id'])) {
                continue;
            }

            $produto = $produtos->get((int) $item['produto_id']);
            if (!$produto) {
                continue;
            }

            $qtdeSaida = $this->resolverQuantidadeSaidaParaEstoque($produto, $item);
            if ($qtdeSaida <= 0) {
                continue;
            }

            $saidasPorProduto[$produto->id] = ($saidasPorProduto[$produto->id] ?? 0) + $qtdeSaida;
        }

        foreach ($saidasPorProduto as $produtoId => $qtdeTotalSaida) {
            $produto = $produtos->get((int) $produtoId);
            if (!$produto) {
                continue;
            }

            if ((int) $produto->estoque_atual < $qtdeTotalSaida) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Estoque insuficiente para o produto selecionado.',
                    'produto_id' => $produto->id,
                ], 422));
            }
        }

        foreach ($itensPayload as $item) {
            $produto = null;
            if (!empty($item['produto_id'])) {
                $produto = $produtos->get((int) $item['produto_id']);
            }

            $pet = null;
            if (!empty($item['pet_id'])) {
                $pet = $pets->get((int) $item['pet_id']);
                if (!$pet) {
                    throw new HttpResponseException(response()->json([
                        'message' => 'Pet invalido para esta loja.',
                        'pet_id' => (int) $item['pet_id'],
                    ], 422));
                }
            }

            $cliente = null;
            if (!empty($item['cliente_id'])) {
                $cliente = $clientes->get((int) $item['cliente_id']);
                if (!$cliente) {
                    throw new HttpResponseException(response()->json([
                        'message' => 'Cliente invalido para esta loja.',
                        'cliente_id' => (int) $item['cliente_id'],
                    ], 422));
                }
            }

            if ($pet && $cliente && (int) $pet->cliente_id !== (int) $cliente->id) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Pet nao pertence ao cliente informado.',
                    'pet_id' => (int) $pet->id,
                    'cliente_id' => (int) $cliente->id,
                ], 422));
            }

            if (!$cliente && $pet) {
                $cliente = $clientes->get((int) $pet->cliente_id) ?: Cliente::query()
                    ->where('id', $pet->cliente_id)
                    ->where('loja_id', $lojaId)
                    ->first(['id', 'loja_id', 'nome']);
            }

            $quantidade = (float) ($item['quantidade'] ?? 0);

            $precoUnitario = array_key_exists('preco_unitario', $item) && $item['preco_unitario'] !== null
                ? (float) $item['preco_unitario']
                : (($produto && $produto->valor_venda !== null) ? (float) $produto->valor_venda : 0.0);

            $subtotal = array_key_exists('subtotal', $item) && $item['subtotal'] !== null
                ? (float) $item['subtotal']
                : round($quantidade * $precoUnitario, 2);

            $perfil = EntradaCaixaItem::resolverPerfilConsumo(
                $pet,
                $item['perfil_pet_tipo'] ?? null
            );
            $ehRacao = $produto && $produto->categoria === 'racao';
            if ($ehRacao && empty($item['pet_id']) && !$perfil) {
                $perfil = 'outros';
            }

            $dataProxima = null;
            $pesoGramas = $item['peso_gramas'] ?? null;
            if ($pesoGramas) {
                $perfilConsumo = $perfil ?: 'outros';
                $consumoDiario = EntradaCaixaItem::consumoDiarioPorPerfil($perfilConsumo);
                $dias = (int) floor(((int) $pesoGramas) / $consumoDiario);
                $dataProxima = Carbon::parse($dataVenda)->addDays($dias)->toDateString();
            }

            $entrada->itens()->create([
                'produto_id' => $item['produto_id'] ?? null,
                'quantidade' => $quantidade,
                'preco_unitario' => $precoUnitario,
                'subtotal' => $subtotal,
                'peso_gramas' => $pesoGramas,
                'perfil_pet_tipo' => $perfil,
                'pet_id' => $pet?->id,
                'cliente_id' => $cliente?->id,
                'data_proxima_compra_estimada' => $dataProxima,
            ]);

            if ($produto) {
                $qtdeSaida = $this->resolverQuantidadeSaidaParaEstoque($produto, $item);
                if ($qtdeSaida > 0) {
                    MovimentacaoEstoque::create([
                        'produto_id' => $produto->id,
                        'tipo' => 'saida',
                        'quantidade' => $qtdeSaida,
                        'motivo' => 'Venda caixa #' . $entrada->id,
                        'usuario_id' => auth()->id(),
                    ]);

                    $produto->decrement('estoque_atual', $qtdeSaida);
                }
            }
        }
    }

    private function resolverQuantidadeSaidaParaEstoque(Produto $produto, array $item): int
    {
        if ($produto->categoria === 'racao' && !empty($item['peso_gramas'])) {
            return max((int) $item['peso_gramas'], 0);
        }

        return max((int) round((float) ($item['quantidade'] ?? 0)), 0);
    }
}
