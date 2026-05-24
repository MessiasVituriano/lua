<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PedidoCompraRequest;
use App\Models\MovimentacaoEstoque;
use App\Models\Pagamento;
use App\Models\PedidoCompra;
use App\Models\PedidoCompraItem;
use App\Models\Produto;
use App\Services\PdfService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidoCompraController extends Controller
{
    public function index(Request $request)
    {
        $lojaId = auth()->user()->loja_id;
        $hoje = Carbon::today();

        $query = PedidoCompra::with(['fornecedor', 'itens.produto', 'usuario'])
            ->where('loja_id', $lojaId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('fornecedor_id')) {
            $query->where('fornecedor_id', $request->fornecedor_id);
        }

        if ($request->filled('data_inicio')) {
            $query->whereDate('created_at', '>=', $request->data_inicio);
        }

        if ($request->filled('data_fim')) {
            $query->whereDate('created_at', '<=', $request->data_fim);
        }

        $lista = $query->orderByDesc('created_at')->get()->map(function ($pedido) use ($hoje) {
            $pedido->atrasado = $pedido->data_estimativa_entrega->lt($hoje)
                && !in_array($pedido->status, ['entregue', 'cancelado']);
            return $pedido;
        });

        $pedidosDoDia = $lista->filter(function ($pedido) use ($hoje) {
            return $pedido->data_estimativa_entrega->isSameDay($hoje);
        })->values();

        return response()->json([
            'data' => $lista->values(),
            'pedidos_do_dia' => $pedidosDoDia,
            'total_atrasados' => $lista->filter(fn($p) => $p->atrasado)->count(),
        ]);
    }

    public function store(PedidoCompraRequest $request)
    {
        $data = $request->validated();
        $itens = $data['itens'];
        unset($data['itens']);

        $data['loja_id'] = auth()->user()->loja_id;
        $data['usuario_id'] = auth()->id();
        $data['status'] = 'pendente';
        $data['valor_total'] = 0;

        DB::beginTransaction();
        try {
            $pedido = PedidoCompra::create($data);

            $valorTotal = $this->salvarItens($pedido, $itens);
            $pedido->update(['valor_total' => $valorTotal]);

            DB::commit();
            return response()->json($pedido->load(['fornecedor', 'itens.produto', 'usuario']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(PedidoCompra $pedidoCompra)
    {
        $hoje = Carbon::today();
        $pedidoCompra->load(['fornecedor', 'banco', 'itens.produto', 'usuario', 'pagamentos']);
        $pedidoCompra->atrasado = $pedidoCompra->data_estimativa_entrega->lt($hoje)
            && !in_array($pedidoCompra->status, ['entregue', 'cancelado']);

        return response()->json($pedidoCompra);
    }

    public function update(PedidoCompraRequest $request, PedidoCompra $pedidoCompra)
    {
        if ($pedidoCompra->status !== 'pendente') {
            return response()->json(['message' => 'Somente pedidos pendentes podem ser editados.'], 422);
        }

        $data = $request->validated();
        $itens = $data['itens'];
        unset($data['itens']);

        DB::beginTransaction();
        try {
            $pedidoCompra->itens()->delete();

            $valorTotal = $this->salvarItens($pedidoCompra, $itens);
            $data['valor_total'] = $valorTotal;
            $pedidoCompra->update($data);

            DB::commit();
            return response()->json($pedidoCompra->load(['fornecedor', 'itens.produto', 'usuario']));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function confirmar(PedidoCompra $pedidoCompra)
    {
        if ($pedidoCompra->status !== 'pendente') {
            return response()->json(['message' => 'Somente pedidos pendentes podem ser confirmados.'], 422);
        }

        if (!$pedidoCompra->data_vencimento) {
            return response()->json(['message' => 'Informe a data de vencimento do pagamento antes de confirmar.'], 422);
        }

        DB::beginTransaction();
        try {
            $pedidoCompra->update([
                'status' => 'confirmado',
                'confirmado_por' => auth()->id(),
                'confirmado_em' => now(),
            ]);

            $this->gerarPagamentos($pedidoCompra);

            DB::commit();
            return response()->json($pedidoCompra->load(['fornecedor', 'itens.produto', 'pagamentos']));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function confirmarEntrega(PedidoCompra $pedidoCompra)
    {
        if ($pedidoCompra->status !== 'confirmado') {
            return response()->json(['message' => 'Somente pedidos confirmados podem ter entrega registrada.'], 422);
        }

        $pedidoCompra->load('itens');

        DB::beginTransaction();
        try {
            $pedidoCompra->update([
                'status' => 'entregue',
                'data_entrega' => Carbon::today(),
                'entregue_por' => auth()->id(),
                'entregue_em' => now(),
            ]);

            foreach ($pedidoCompra->itens as $item) {
                MovimentacaoEstoque::create([
                    'produto_id' => $item->produto_id,
                    'tipo' => 'entrada',
                    'quantidade' => $item->quantidade,
                    'motivo' => 'Pedido de compra #' . $pedidoCompra->id,
                    'usuario_id' => auth()->id(),
                ]);

                Produto::where('id', $item->produto_id)->increment('estoque_atual', $item->quantidade);
            }

            DB::commit();
            return response()->json($pedidoCompra->load(['fornecedor', 'itens.produto', 'pagamentos']));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function cancelar(PedidoCompra $pedidoCompra)
    {
        if (in_array($pedidoCompra->status, ['entregue', 'cancelado'])) {
            return response()->json(['message' => 'Pedido não pode ser cancelado neste status.'], 422);
        }

        $pedidoCompra->update([
            'status' => 'cancelado',
            'cancelado_por' => auth()->id(),
            'cancelado_em' => now(),
        ]);

        return response()->json($pedidoCompra->load(['fornecedor', 'itens.produto']));
    }

    public function atualizarDadosPagamento(Request $request, PedidoCompra $pedidoCompra)
    {
        if (in_array($pedidoCompra->status, ['entregue', 'cancelado'])) {
            return response()->json(['message' => 'Não é possível alterar dados de pagamento neste status.'], 422);
        }

        $data = $request->validate([
            'data_vencimento'    => ['nullable', 'date'],
            'forma_pagamento'    => ['nullable', \Illuminate\Validation\Rule::in(['dinheiro', 'pix', 'boleto', 'transferencia'])],
            'banco_id'           => ['nullable', 'exists:bancos,id'],
            'quantidade_parcelas'=> ['nullable', 'integer', 'min:1', 'max:60'],
            'recorrencia_dias'   => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        DB::beginTransaction();
        try {
            $pedidoCompra->update($data);

            // Se já confirmado, regenera os pagamentos pendentes
            if ($pedidoCompra->status === 'confirmado') {
                $pedidoCompra->pagamentos()->where('status', 'pendente')->delete();
                if ($pedidoCompra->data_vencimento) {
                    $this->gerarPagamentos($pedidoCompra->fresh());
                }
            }

            DB::commit();
            return response()->json($pedidoCompra->load(['fornecedor', 'itens.produto', 'pagamentos']));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function pdf(PedidoCompra $pedidoCompra, PdfService $pdfService, Request $request): \Illuminate\Http\Response
    {
        $pedidoCompra->load(['loja', 'fornecedor', 'banco', 'itens.produto', 'usuario']);

        $semValores = $request->boolean('sem_valores');
        $filename   = 'pedido-compra-' . str_pad($pedidoCompra->id, 6, '0', STR_PAD_LEFT);
        $titulo     = $semValores
            ? 'Ordem de Compra #' . str_pad($pedidoCompra->id, 6, '0', STR_PAD_LEFT)
            : 'Pedido de Compra #' . str_pad($pedidoCompra->id, 6, '0', STR_PAD_LEFT);

        return $pdfService->stream(
            'pdf.pedido-compra',
            ['pedido' => $pedidoCompra, 'semValores' => $semValores],
            $filename,
            $titulo
        );
    }

    private function salvarItens(PedidoCompra $pedido, array $itens): float
    {
        $valorTotal = 0;

        foreach ($itens as $item) {
            $produto = Produto::findOrFail($item['produto_id']);
            $valorUnitario = isset($item['valor_unitario']) ? (float) $item['valor_unitario'] : (float) $produto->valor_custo;
            $valorItemTotal = round($valorUnitario * $item['quantidade'], 2);

            PedidoCompraItem::create([
                'pedido_compra_id' => $pedido->id,
                'produto_id' => $item['produto_id'],
                'quantidade' => $item['quantidade'],
                'valor_unitario' => $valorUnitario,
                'valor_total' => $valorItemTotal,
            ]);

            $valorTotal += $valorItemTotal;
        }

        return round($valorTotal, 2);
    }

    private function gerarPagamentos(PedidoCompra $pedido): void
    {
        $quantidadeParcelas = max(1, (int) ($pedido->quantidade_parcelas ?? 1));
        $recorrenciaDias = max(1, (int) ($pedido->recorrencia_dias ?? 30));
        $valorTotalCentavos = (int) round((float) $pedido->valor_total * 100);
        $baseCentavos = intdiv($valorTotalCentavos, $quantidadeParcelas);
        $restoCentavos = $valorTotalCentavos % $quantidadeParcelas;
        $dataInicial = Carbon::parse($pedido->data_vencimento)->startOfDay();

        $fornecedor = $pedido->fornecedor;
        $descricaoBase = 'Pedido #' . $pedido->id . ($fornecedor ? ' - ' . $fornecedor->nome : '');

        for ($i = 0; $i < $quantidadeParcelas; $i++) {
            $valorParcelaCentavos = $baseCentavos + ($i < $restoCentavos ? 1 : 0);
            $descricao = $quantidadeParcelas > 1
                ? $descricaoBase . ' (' . ($i + 1) . '/' . $quantidadeParcelas . ')'
                : $descricaoBase;

            Pagamento::create([
                'loja_id' => $pedido->loja_id,
                'pedido_compra_id' => $pedido->id,
                'fornecedor_id' => $pedido->fornecedor_id,
                'categoria' => 'fornecedor',
                'descricao' => $descricao,
                'valor_total' => $valorParcelaCentavos / 100,
                'valor_pago' => 0,
                'data_vencimento' => (clone $dataInicial)->addDays($i * $recorrenciaDias)->toDateString(),
                'forma_pagamento' => $pedido->forma_pagamento,
                'banco_id' => $pedido->banco_id,
                'status' => 'pendente',
                'recorrente' => false,
            ]);
        }
    }
}
