<?php

namespace Database\Seeders;

use App\Models\Banco;
use App\Models\CaixaDiario;
use App\Models\EntradaCaixa;
use App\Models\Fornecedor;
use App\Models\Loja;
use App\Models\MovimentacaoEstoque;
use App\Models\Pagamento;
use App\Models\Produto;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FluxoCaixaSeeder extends Seeder
{
    public function run(): void
    {
        $loja = Loja::first();
        $admin = User::where('role', 'admin')->first();
        $bancos = Banco::where('ativo', true)->pluck('id')->toArray();
        $fornecedores = Fornecedor::pluck('id')->toArray();

        // ── Produtos ──
        $produtos = $this->criarProdutos($loja, $fornecedores, $admin);

        // ── Pagamentos recorrentes ──
        $this->criarPagamentosRecorrentes($loja, $fornecedores, $bancos);

        // ── Fluxo de caixa dos ultimos 3 meses ──
        $inicio = Carbon::now()->subMonths(3)->startOfMonth();
        $fim = Carbon::yesterday();

        $diasUteis = [];
        $dia = $inicio->copy();
        while ($dia->lte($fim)) {
            // Pula domingos (petshop fecha domingo)
            if ($dia->dayOfWeek !== Carbon::SUNDAY) {
                $diasUteis[] = $dia->copy();
            }
            $dia->addDay();
        }

        foreach ($diasUteis as $data) {
            $this->criarCaixaDia($loja, $admin, $bancos, $data);
        }

        // ── Pagamentos avulsos (boletos, impostos) por mes ──
        $this->criarPagamentosAvulsos($loja, $fornecedores, $bancos, $inicio, $fim);
    }

    private function criarProdutos(Loja $loja, array $fornecedores, User $admin): array
    {
        $itens = [
            ['nome' => 'Racao Premier Adulto 15kg', 'categoria' => 'racao', 'valor_custo' => 120.00, 'margem' => 35, 'estoque_min' => 5],
            ['nome' => 'Racao Royal Canin Mini 7.5kg', 'categoria' => 'racao', 'valor_custo' => 95.00, 'margem' => 40, 'estoque_min' => 5],
            ['nome' => 'Racao Golden Gatos 10kg', 'categoria' => 'racao', 'valor_custo' => 85.00, 'margem' => 38, 'estoque_min' => 3],
            ['nome' => 'Antipulgas Frontline Plus', 'categoria' => 'medicamento', 'valor_custo' => 45.00, 'margem' => 50, 'estoque_min' => 10],
            ['nome' => 'Vermifugo Drontal Plus', 'categoria' => 'medicamento', 'valor_custo' => 28.00, 'margem' => 55, 'estoque_min' => 8],
            ['nome' => 'Shampoo Sanol Dog 500ml', 'categoria' => 'higiene', 'valor_custo' => 12.00, 'margem' => 60, 'estoque_min' => 10],
            ['nome' => 'Condicionador Sanol 500ml', 'categoria' => 'higiene', 'valor_custo' => 14.00, 'margem' => 55, 'estoque_min' => 8],
            ['nome' => 'Coleira Antipulgas', 'categoria' => 'acessorio', 'valor_custo' => 18.00, 'margem' => 65, 'estoque_min' => 10],
            ['nome' => 'Brinquedo Mordedor Corda', 'categoria' => 'acessorio', 'valor_custo' => 8.00, 'margem' => 80, 'estoque_min' => 15],
            ['nome' => 'Cama Pet Grande', 'categoria' => 'acessorio', 'valor_custo' => 55.00, 'margem' => 45, 'estoque_min' => 3],
            ['nome' => 'Tapete Higienico 30un', 'categoria' => 'higiene', 'valor_custo' => 22.00, 'margem' => 50, 'estoque_min' => 8],
            ['nome' => 'Racao Whiskas Sache 85g', 'categoria' => 'racao', 'valor_custo' => 2.50, 'margem' => 60, 'estoque_min' => 30],
        ];

        $produtos = [];
        foreach ($itens as $item) {
            $produto = Produto::create([
                'loja_id' => $loja->id,
                'fornecedor_id' => $fornecedores[array_rand($fornecedores)],
                'nome' => $item['nome'],
                'categoria' => $item['categoria'],
                'valor_custo' => $item['valor_custo'],
                'margem' => $item['margem'],
                'valor_venda' => Produto::calcularValorVenda($item['valor_custo'], $item['margem']),
                'estoque_atual' => rand(0, 40),
                'estoque_min' => $item['estoque_min'],
            ]);

            // Movimentacao inicial de estoque
            if ($produto->estoque_atual > 0) {
                MovimentacaoEstoque::create([
                    'produto_id' => $produto->id,
                    'tipo' => 'entrada',
                    'quantidade' => $produto->estoque_atual,
                    'motivo' => 'Estoque inicial',
                    'usuario_id' => $admin->id,
                    'created_at' => Carbon::now()->subMonths(3),
                    'updated_at' => Carbon::now()->subMonths(3),
                ]);
            }

            $produtos[] = $produto;
        }

        return $produtos;
    }

    private function criarCaixaDia(Loja $loja, User $admin, array $bancos, Carbon $data): void
    {
        $caixa = CaixaDiario::create([
            'loja_id' => $loja->id,
            'data' => $data->toDateString(),
            'status' => 'fechado',
            'fechado_por' => $admin->id,
            'fechado_em' => $data->copy()->setHour(18)->setMinute(rand(0, 59)),
            'autorizado_por' => $admin->id,
            'autorizado_em' => $data->copy()->setHour(18)->setMinute(rand(0, 59)),
            'created_at' => $data->copy()->setHour(8),
            'updated_at' => $data->copy()->setHour(18),
        ]);

        $formas = ['dinheiro', 'pix', 'cartao_debito', 'cartao_credito'];
        $totalEntradas = 0;

        // Sabado fatura menos
        $fator = $data->dayOfWeek === Carbon::SATURDAY ? 0.6 : 1.0;

        // Gerar entre 5 e 15 entradas por dia
        $numEntradas = rand((int)(5 * $fator), (int)(15 * $fator));
        for ($i = 0; $i < $numEntradas; $i++) {
            $forma = $formas[array_rand($formas)];
            $valor = $this->valorAleatorio($forma);

            $bancoId = null;
            if ($forma !== 'dinheiro' && !empty($bancos)) {
                $bancoId = $bancos[array_rand($bancos)];
            }

            $descricoes = [
                'dinheiro' => ['Venda balcao', 'Banho e tosa', 'Consulta', 'Venda racao', 'Servico'],
                'pix' => ['PIX cliente', 'Venda online', 'Banho e tosa PIX', 'Racao PIX'],
                'cartao_debito' => ['Venda debito', 'Banho debito', 'Acessorios debito'],
                'cartao_credito' => ['Venda credito', 'Racao credito', 'Consulta credito', 'Medicamento credito'],
            ];

            EntradaCaixa::create([
                'caixa_diario_id' => $caixa->id,
                'forma_recebimento' => $forma,
                'banco_id' => $bancoId,
                'valor' => $valor,
                'descricao' => $descricoes[$forma][array_rand($descricoes[$forma])],
                'created_at' => $data->copy()->setHour(rand(8, 17))->setMinute(rand(0, 59)),
                'updated_at' => $data->copy()->setHour(rand(8, 17))->setMinute(rand(0, 59)),
            ]);

            $totalEntradas += $valor;
        }

        // Buscar saidas do dia (pagamentos pagos nesta data)
        $totalSaidas = Pagamento::where('loja_id', $loja->id)
            ->where('data_pagamento', $data->toDateString())
            ->whereIn('status', ['pago', 'parcial'])
            ->sum('valor_pago');

        $caixa->update([
            'total_entradas' => $totalEntradas,
            'total_saidas' => $totalSaidas,
            'saldo' => $totalEntradas - $totalSaidas,
        ]);
    }

    private function criarPagamentosRecorrentes(Loja $loja, array $fornecedores, array $bancos): void
    {
        $fixos = [
            ['descricao' => 'Aluguel loja', 'categoria' => 'custo_fixo', 'valor_total' => 3500.00, 'dia' => 5],
            ['descricao' => 'Energia eletrica', 'categoria' => 'custo_fixo', 'valor_total' => 850.00, 'dia' => 10],
            ['descricao' => 'Agua e esgoto', 'categoria' => 'custo_fixo', 'valor_total' => 180.00, 'dia' => 12],
            ['descricao' => 'Internet', 'categoria' => 'custo_fixo', 'valor_total' => 150.00, 'dia' => 15],
            ['descricao' => 'Salario - Atendente 1', 'categoria' => 'funcionario', 'valor_total' => 2200.00, 'dia' => 5],
            ['descricao' => 'Salario - Atendente 2', 'categoria' => 'funcionario', 'valor_total' => 2200.00, 'dia' => 5],
            ['descricao' => 'Salario - Tosador', 'categoria' => 'funcionario', 'valor_total' => 2800.00, 'dia' => 5],
            ['descricao' => 'INSS', 'categoria' => 'imposto', 'valor_total' => 1200.00, 'dia' => 20],
            ['descricao' => 'Simples Nacional', 'categoria' => 'imposto', 'valor_total' => 980.00, 'dia' => 20],
            ['descricao' => 'Contador', 'categoria' => 'custo_fixo', 'valor_total' => 450.00, 'dia' => 10],
        ];

        $inicio = Carbon::now()->subMonths(3)->startOfMonth();

        for ($m = 0; $m < 3; $m++) {
            $mesRef = $inicio->copy()->addMonths($m);

            foreach ($fixos as $fixo) {
                $dia = min($fixo['dia'], $mesRef->copy()->endOfMonth()->day);
                $vencimento = $mesRef->copy()->day($dia);

                $pago = $vencimento->lt(Carbon::today());
                $forma = ['pix', 'boleto', 'transferencia'][array_rand(['pix', 'boleto', 'transferencia'])];

                Pagamento::create([
                    'loja_id' => $loja->id,
                    'categoria' => $fixo['categoria'],
                    'descricao' => $fixo['descricao'],
                    'valor_total' => $fixo['valor_total'],
                    'valor_pago' => $pago ? $fixo['valor_total'] : 0,
                    'data_vencimento' => $vencimento,
                    'data_pagamento' => $pago ? $vencimento : null,
                    'forma_pagamento' => $pago ? $forma : null,
                    'banco_id' => $pago && !empty($bancos) ? $bancos[array_rand($bancos)] : null,
                    'status' => $pago ? 'pago' : ($vencimento->lt(Carbon::today()) ? 'atrasado' : 'pendente'),
                    'recorrente' => true,
                    'dia_recorrencia' => $fixo['dia'],
                    'created_at' => $mesRef->copy()->startOfMonth(),
                    'updated_at' => $pago ? $vencimento : $mesRef->copy()->startOfMonth(),
                ]);
            }
        }
    }

    private function criarPagamentosAvulsos(Loja $loja, array $fornecedores, array $bancos, Carbon $inicio, Carbon $fim): void
    {
        $avulsos = [
            ['descricao' => 'Compra racao Premier - lote', 'categoria' => 'fornecedor', 'min' => 1500, 'max' => 4000],
            ['descricao' => 'Compra racao Royal Canin', 'categoria' => 'fornecedor', 'min' => 1200, 'max' => 3000],
            ['descricao' => 'Reposicao medicamentos', 'categoria' => 'fornecedor', 'min' => 500, 'max' => 1500],
            ['descricao' => 'Compra acessorios Chalesco', 'categoria' => 'fornecedor', 'min' => 300, 'max' => 800],
            ['descricao' => 'Material limpeza', 'categoria' => 'outros', 'min' => 100, 'max' => 300],
            ['descricao' => 'Manutencao ar condicionado', 'categoria' => 'outros', 'min' => 200, 'max' => 500],
            ['descricao' => 'Compra higiene Sanol', 'categoria' => 'fornecedor', 'min' => 400, 'max' => 1000],
            ['descricao' => 'Boleto equipamento banho', 'categoria' => 'boleto', 'min' => 350, 'max' => 700],
        ];

        for ($m = 0; $m < 3; $m++) {
            $mesRef = $inicio->copy()->addMonths($m);

            // 4-8 pagamentos avulsos por mes
            $qtd = rand(4, 8);
            $selecionados = array_rand($avulsos, min($qtd, count($avulsos)));
            if (!is_array($selecionados)) $selecionados = [$selecionados];

            foreach ($selecionados as $idx) {
                $item = $avulsos[$idx];
                $valor = round(rand($item['min'] * 100, $item['max'] * 100) / 100, 2);
                $diaVenc = rand(1, $mesRef->copy()->endOfMonth()->day);
                $vencimento = $mesRef->copy()->day($diaVenc);

                $pago = $vencimento->lt(Carbon::today());
                $forma = ['pix', 'boleto', 'transferencia'][array_rand(['pix', 'boleto', 'transferencia'])];

                $fornecedorId = null;
                if ($item['categoria'] === 'fornecedor' && !empty($fornecedores)) {
                    $fornecedorId = $fornecedores[array_rand($fornecedores)];
                }

                Pagamento::create([
                    'loja_id' => $loja->id,
                    'fornecedor_id' => $fornecedorId,
                    'categoria' => $item['categoria'],
                    'descricao' => $item['descricao'],
                    'valor_total' => $valor,
                    'valor_pago' => $pago ? $valor : 0,
                    'data_vencimento' => $vencimento,
                    'data_pagamento' => $pago ? $vencimento : null,
                    'forma_pagamento' => $pago ? $forma : null,
                    'banco_id' => $pago && !empty($bancos) ? $bancos[array_rand($bancos)] : null,
                    'status' => $pago ? 'pago' : ($vencimento->lt(Carbon::today()) ? 'atrasado' : 'pendente'),
                    'observacao' => $item['categoria'] === 'fornecedor' ? 'Nota fiscal recebida' : null,
                    'created_at' => $vencimento->copy()->subDays(rand(1, 5)),
                    'updated_at' => $pago ? $vencimento : $vencimento->copy()->subDays(rand(1, 5)),
                ]);
            }
        }
    }

    private function valorAleatorio(string $forma): float
    {
        // Valores realistas para petshop
        $ranges = [
            'dinheiro' => [15, 200],
            'pix' => [20, 350],
            'cartao_debito' => [30, 250],
            'cartao_credito' => [50, 500],
        ];

        $range = $ranges[$forma];
        return round(rand($range[0] * 100, $range[1] * 100) / 100, 2);
    }
}
