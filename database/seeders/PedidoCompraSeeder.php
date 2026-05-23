<?php

namespace Database\Seeders;

use App\Models\Banco;
use App\Models\Fornecedor;
use App\Models\MovimentacaoEstoque;
use App\Models\Pagamento;
use App\Models\PedidoCompra;
use App\Models\PedidoCompraItem;
use App\Models\Produto;
use App\Models\Loja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PedidoCompraSeeder extends Seeder
{
    public function run(): void
    {
        $loja  = Loja::first();
        $admin = User::where('role', 'admin')->first();
        $banco = Banco::first();

        // ── Fornecedores ─────────────────────────────────────────────────────
        $fRacao = Fornecedor::firstOrCreate(
            ['nome' => 'PremieR Pet'],
            ['categoria' => 'racao', 'telefone' => '(11) 91111-1111', 'ativo' => true]
        );

        $fMed = Fornecedor::firstOrCreate(
            ['nome' => 'Ouro Fino'],
            ['categoria' => 'medicamento', 'telefone' => '(11) 93333-3333', 'ativo' => true]
        );

        $fAcess = Fornecedor::firstOrCreate(
            ['nome' => 'Chalesco'],
            ['categoria' => 'acessorio', 'telefone' => '(11) 94444-4444', 'ativo' => true]
        );

        $fHig = Fornecedor::firstOrCreate(
            ['nome' => 'Sanol'],
            ['categoria' => 'higiene', 'telefone' => '(11) 95555-5555', 'ativo' => true]
        );

        // ── Produtos ─────────────────────────────────────────────────────────
        $p1 = Produto::firstOrCreate(
            ['nome' => 'Ração Premier Adulto 15kg', 'loja_id' => $loja->id],
            [
                'fornecedor_id' => $fRacao->id,
                'categoria'     => 'racao',
                'valor_custo'   => 120.00,
                'margem'        => 35,
                'valor_venda'   => 162.00,
                'estoque_atual' => 50,
                'estoque_min'   => 5,
                'ativo'         => true,
            ]
        );

        $p2 = Produto::firstOrCreate(
            ['nome' => 'Ração Royal Canin Mini Adulto 7,5kg', 'loja_id' => $loja->id],
            [
                'fornecedor_id' => $fRacao->id,
                'categoria'     => 'racao',
                'valor_custo'   => 180.00,
                'margem'        => 40,
                'valor_venda'   => 252.00,
                'estoque_atual' => 30,
                'estoque_min'   => 3,
                'ativo'         => true,
            ]
        );

        $p3 = Produto::firstOrCreate(
            ['nome' => 'Shampoo Neutro Pet 500ml', 'loja_id' => $loja->id],
            [
                'fornecedor_id' => $fHig->id,
                'categoria'     => 'higiene',
                'valor_custo'   => 12.50,
                'margem'        => 60,
                'valor_venda'   => 20.00,
                'estoque_atual' => 40,
                'estoque_min'   => 10,
                'ativo'         => true,
            ]
        );

        $p4 = Produto::firstOrCreate(
            ['nome' => 'Coleira Ajustável P', 'loja_id' => $loja->id],
            [
                'fornecedor_id' => $fAcess->id,
                'categoria'     => 'acessorio',
                'valor_custo'   => 8.00,
                'margem'        => 75,
                'valor_venda'   => 14.00,
                'estoque_atual' => 60,
                'estoque_min'   => 10,
                'ativo'         => true,
            ]
        );

        $p5 = Produto::firstOrCreate(
            ['nome' => 'Vermífugo Drontal Plus 4 comp', 'loja_id' => $loja->id],
            [
                'fornecedor_id' => $fMed->id,
                'categoria'     => 'medicamento',
                'valor_custo'   => 28.00,
                'margem'        => 50,
                'valor_venda'   => 42.00,
                'estoque_atual' => 25,
                'estoque_min'   => 5,
                'ativo'         => true,
            ]
        );

        // ── Pedidos ───────────────────────────────────────────────────────────

        DB::transaction(function () use ($loja, $admin, $banco, $fRacao, $fMed, $fAcess, $fHig, $p1, $p2, $p3, $p4, $p5) {

            // 1. PENDENTE – sem dados de pagamento ainda
            $this->criarPedido(
                loja: $loja,
                usuario: $admin,
                fornecedor: $fRacao,
                status: 'pendente',
                observacao: 'Reposição mensal de rações – aguardando negociação de prazo',
                estimativaEntrega: Carbon::today()->addDays(7),
                itens: [
                    ['produto' => $p1, 'quantidade' => 10, 'valor_unitario' => 120.00],
                    ['produto' => $p2, 'quantidade' => 5,  'valor_unitario' => 180.00],
                ],
            );

            // 2. PENDENTE – com dados de pagamento preenchidos (pronto para confirmar)
            $this->criarPedido(
                loja: $loja,
                usuario: $admin,
                fornecedor: $fHig,
                status: 'pendente',
                observacao: 'Reposição de higiene – boleto 30 dias',
                estimativaEntrega: Carbon::today()->addDays(5),
                dataVencimento: Carbon::today()->addDays(30),
                formaPagamento: 'boleto',
                quantidadeParcelas: 1,
                recorrenciaDias: 30,
                itens: [
                    ['produto' => $p3, 'quantidade' => 20, 'valor_unitario' => 12.50],
                ],
            );

            // 3. CONFIRMADO – 1 parcela
            $p3confirmado = $this->criarPedido(
                loja: $loja,
                usuario: $admin,
                fornecedor: $fAcess,
                status: 'confirmado',
                observacao: 'Pedido de acessórios confirmado – pagamento único',
                estimativaEntrega: Carbon::today()->addDays(3),
                dataVencimento: Carbon::today()->addDays(15),
                formaPagamento: 'pix',
                quantidadeParcelas: 1,
                recorrenciaDias: 30,
                confirmadoPor: $admin,
                itens: [
                    ['produto' => $p4, 'quantidade' => 30, 'valor_unitario' => 8.00],
                ],
            );
            $this->gerarPagamentos($p3confirmado);

            // 4. CONFIRMADO – 3 parcelas
            $p4confirmado = $this->criarPedido(
                loja: $loja,
                usuario: $admin,
                fornecedor: $fRacao,
                status: 'confirmado',
                observacao: 'Grande reposição de rações – parcelado em 3x',
                estimativaEntrega: Carbon::today()->addDays(10),
                dataVencimento: Carbon::today()->addDays(30),
                formaPagamento: 'boleto',
                quantidadeParcelas: 3,
                recorrenciaDias: 30,
                confirmadoPor: $admin,
                itens: [
                    ['produto' => $p1, 'quantidade' => 20, 'valor_unitario' => 120.00],
                    ['produto' => $p2, 'quantidade' => 10, 'valor_unitario' => 180.00],
                ],
            );
            $this->gerarPagamentos($p4confirmado);

            // 5. ENTREGUE – com movimentação de estoque
            $p5entregue = $this->criarPedido(
                loja: $loja,
                usuario: $admin,
                fornecedor: $fMed,
                status: 'entregue',
                observacao: 'Pedido de medicamentos recebido e conferido',
                estimativaEntrega: Carbon::today()->subDays(2),
                dataEntrega: Carbon::today()->subDays(1),
                dataVencimento: Carbon::today()->addDays(30),
                formaPagamento: 'transferencia',
                quantidadeParcelas: 1,
                recorrenciaDias: 30,
                confirmadoPor: $admin,
                entreguePor: $admin,
                itens: [
                    ['produto' => $p5, 'quantidade' => 10, 'valor_unitario' => 28.00],
                ],
            );
            $this->gerarPagamentos($p5entregue);
            $this->gerarMovimentacoes($p5entregue, $admin);

            // 6. ENTREGUE – ração paga parcelada
            $p6entregue = $this->criarPedido(
                loja: $loja,
                usuario: $admin,
                fornecedor: $fRacao,
                status: 'entregue',
                observacao: 'Reposição entregue – parcelado 2x',
                estimativaEntrega: Carbon::today()->subDays(5),
                dataEntrega: Carbon::today()->subDays(4),
                dataVencimento: Carbon::today()->subDays(4),
                formaPagamento: 'boleto',
                quantidadeParcelas: 2,
                recorrenciaDias: 30,
                confirmadoPor: $admin,
                entreguePor: $admin,
                itens: [
                    ['produto' => $p1, 'quantidade' => 15, 'valor_unitario' => 120.00],
                    ['produto' => $p3, 'quantidade' => 10, 'valor_unitario' => 12.50],
                ],
            );
            $this->gerarPagamentos($p6entregue);
            $this->gerarMovimentacoes($p6entregue, $admin);

            // 7. CANCELADO
            $this->criarPedido(
                loja: $loja,
                usuario: $admin,
                fornecedor: $fAcess,
                status: 'cancelado',
                observacao: 'Pedido cancelado por falta de estoque no fornecedor',
                estimativaEntrega: Carbon::today()->addDays(2),
                canceladoPor: $admin,
                itens: [
                    ['produto' => $p4, 'quantidade' => 50, 'valor_unitario' => 8.00],
                ],
            );
        });

        $this->command->info('PedidoCompraSeeder: 7 pedidos criados (2 pendentes, 2 confirmados, 2 entregues, 1 cancelado).');
    }

    private function criarPedido(
        $loja,
        $usuario,
        $fornecedor,
        string $status,
        string $observacao,
        ?Carbon $estimativaEntrega = null,
        ?Carbon $dataEntrega = null,
        ?Carbon $dataVencimento = null,
        ?string $formaPagamento = null,
        int $quantidadeParcelas = 1,
        int $recorrenciaDias = 30,
        $confirmadoPor = null,
        $entreguePor = null,
        $canceladoPor = null,
        array $itens = [],
        $banco = null,
    ): PedidoCompra {
        $valorTotal = collect($itens)->sum(fn($i) => $i['quantidade'] * $i['valor_unitario']);

        $pedido = PedidoCompra::create([
            'loja_id'                 => $loja->id,
            'fornecedor_id'           => $fornecedor->id,
            'usuario_id'              => $usuario->id,
            'status'                  => $status,
            'observacao'              => $observacao,
            'valor_total'             => $valorTotal,
            'data_estimativa_entrega' => $estimativaEntrega,
            'data_entrega'            => $dataEntrega,
            'data_vencimento'         => $dataVencimento,
            'forma_pagamento'         => $formaPagamento,
            'banco_id'                => $banco?->id,
            'quantidade_parcelas'     => $quantidadeParcelas,
            'recorrencia_dias'        => $recorrenciaDias,
            'confirmado_por'          => $confirmadoPor?->id,
            'confirmado_em'           => $confirmadoPor ? now() : null,
            'entregue_por'            => $entreguePor?->id,
            'entregue_em'             => $entreguePor ? now() : null,
            'cancelado_por'           => $canceladoPor?->id,
            'cancelado_em'            => $canceladoPor ? now() : null,
        ]);

        foreach ($itens as $item) {
            PedidoCompraItem::create([
                'pedido_compra_id' => $pedido->id,
                'produto_id'       => $item['produto']->id,
                'quantidade'       => $item['quantidade'],
                'valor_unitario'   => $item['valor_unitario'],
                'valor_total'      => $item['quantidade'] * $item['valor_unitario'],
            ]);
        }

        return $pedido;
    }

    private function gerarPagamentos(PedidoCompra $pedido): void
    {
        $qtd             = max(1, (int) ($pedido->quantidade_parcelas ?? 1));
        $recorrencia     = max(1, (int) ($pedido->recorrencia_dias ?? 30));
        $totalCentavos   = (int) round((float) $pedido->valor_total * 100);
        $baseCentavos    = intdiv($totalCentavos, $qtd);
        $restoCentavos   = $totalCentavos % $qtd;
        $dataInicial     = Carbon::parse($pedido->data_vencimento)->startOfDay();
        $fornecedor      = $pedido->fornecedor;
        $descricaoBase   = 'Pedido #' . $pedido->id . ($fornecedor ? ' - ' . $fornecedor->nome : '');

        for ($i = 0; $i < $qtd; $i++) {
            $valorParcela = ($baseCentavos + ($i < $restoCentavos ? 1 : 0)) / 100;
            $descricao    = $qtd > 1
                ? $descricaoBase . ' (' . ($i + 1) . '/' . $qtd . ')'
                : $descricaoBase;

            Pagamento::create([
                'loja_id'          => $pedido->loja_id,
                'pedido_compra_id' => $pedido->id,
                'fornecedor_id'    => $pedido->fornecedor_id,
                'categoria'        => 'fornecedor',
                'descricao'        => $descricao,
                'valor_total'      => $valorParcela,
                'valor_pago'       => 0,
                'data_vencimento'  => (clone $dataInicial)->addDays($i * $recorrencia)->toDateString(),
                'forma_pagamento'  => $pedido->forma_pagamento,
                'banco_id'         => $pedido->banco_id,
                'status'           => 'pendente',
                'recorrente'       => false,
            ]);
        }
    }

    private function gerarMovimentacoes(PedidoCompra $pedido, $usuario): void
    {
        $pedido->load('itens');

        foreach ($pedido->itens as $item) {
            MovimentacaoEstoque::create([
                'produto_id' => $item->produto_id,
                'tipo'       => 'entrada',
                'quantidade' => $item->quantidade,
                'motivo'     => 'Pedido de compra #' . $pedido->id,
                'usuario_id' => $usuario->id,
            ]);

            Produto::where('id', $item->produto_id)->increment('estoque_atual', $item->quantidade);
        }
    }
}
