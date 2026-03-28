<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaixaDiario;
use App\Models\EntradaCaixa;
use App\Models\Pagamento;
use App\Models\Produto;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $lojaId = auth()->user()->loja_id;

        // Periodo padrao: mes atual
        $inicio = $request->input('data_inicio', Carbon::now()->startOfMonth()->toDateString());
        $fim = $request->input('data_fim', Carbon::now()->endOfMonth()->toDateString());
        $agrupamento = $request->input('agrupamento', 'dia'); // dia ou mes

        // ── Cards resumo ──
        $totalEntradas = CaixaDiario::where('loja_id', $lojaId)
            ->whereBetween('data', [$inicio, $fim])
            ->sum('total_entradas');

        $totalSaidas = Pagamento::where('loja_id', $lojaId)
            ->whereIn('status', ['pago', 'parcial'])
            ->whereBetween('data_pagamento', [$inicio, $fim])
            ->sum('valor_pago');

        $saldo = $totalEntradas - $totalSaidas;

        $caixasAbertos = CaixaDiario::where('loja_id', $lojaId)
            ->where('status', 'aberto')
            ->count();

        $pagamentosPendentes = Pagamento::where('loja_id', $lojaId)
            ->whereIn('status', ['pendente', 'atrasado', 'parcial'])
            ->count();

        $pagamentosAtrasados = Pagamento::where('loja_id', $lojaId)
            ->where('status', 'atrasado')
            ->count();

        $estoqueBaixo = Produto::where('loja_id', $lojaId)
            ->where('ativo', true)
            ->whereNotNull('estoque_min')
            ->whereColumn('estoque_atual', '<=', 'estoque_min')
            ->count();

        // ── Periodo anterior (para comparativo) ──
        $dias = Carbon::parse($inicio)->diffInDays(Carbon::parse($fim)) + 1;
        $inicioAnterior = Carbon::parse($inicio)->subDays($dias)->toDateString();
        $fimAnterior = Carbon::parse($inicio)->subDay()->toDateString();

        $entradasAnterior = CaixaDiario::where('loja_id', $lojaId)
            ->whereBetween('data', [$inicioAnterior, $fimAnterior])
            ->sum('total_entradas');

        $saidasAnterior = Pagamento::where('loja_id', $lojaId)
            ->whereIn('status', ['pago', 'parcial'])
            ->whereBetween('data_pagamento', [$inicioAnterior, $fimAnterior])
            ->sum('valor_pago');

        // ── Grafico: entradas e saidas por periodo ──
        if ($agrupamento === 'mes') {
            $graficoEntradas = CaixaDiario::where('loja_id', $lojaId)
                ->whereBetween('data', [$inicio, $fim])
                ->selectRaw("to_char(data, 'YYYY-MM') as periodo, SUM(total_entradas) as total")
                ->groupBy('periodo')
                ->orderBy('periodo')
                ->pluck('total', 'periodo');

            $graficoSaidas = Pagamento::where('loja_id', $lojaId)
                ->whereIn('status', ['pago', 'parcial'])
                ->whereBetween('data_pagamento', [$inicio, $fim])
                ->selectRaw("to_char(data_pagamento, 'YYYY-MM') as periodo, SUM(valor_pago) as total")
                ->groupBy('periodo')
                ->orderBy('periodo')
                ->pluck('total', 'periodo');
        } else {
            $graficoEntradas = CaixaDiario::where('loja_id', $lojaId)
                ->whereBetween('data', [$inicio, $fim])
                ->selectRaw("data::text as periodo, total_entradas as total")
                ->orderBy('data')
                ->pluck('total', 'periodo');

            $graficoSaidas = Pagamento::where('loja_id', $lojaId)
                ->whereIn('status', ['pago', 'parcial'])
                ->whereBetween('data_pagamento', [$inicio, $fim])
                ->selectRaw("data_pagamento::text as periodo, SUM(valor_pago) as total")
                ->groupBy('periodo')
                ->orderBy('periodo')
                ->pluck('total', 'periodo');
        }

        // Unificar labels dos graficos
        $allLabels = collect($graficoEntradas->keys())
            ->merge($graficoSaidas->keys())
            ->unique()
            ->sort()
            ->values();

        $grafico = [
            'labels' => $allLabels,
            'entradas' => $allLabels->map(fn ($l) => (float) ($graficoEntradas[$l] ?? 0)),
            'saidas' => $allLabels->map(fn ($l) => (float) ($graficoSaidas[$l] ?? 0)),
        ];

        // ── Entradas por forma de recebimento ──
        $entradasPorForma = EntradaCaixa::whereHas('caixaDiario', function ($q) use ($lojaId, $inicio, $fim) {
                $q->where('loja_id', $lojaId)->whereBetween('data', [$inicio, $fim]);
            })
            ->selectRaw('forma_recebimento, SUM(valor) as total')
            ->groupBy('forma_recebimento')
            ->pluck('total', 'forma_recebimento');

        // ── Saidas por categoria ──
        $saidasPorCategoria = Pagamento::where('loja_id', $lojaId)
            ->whereIn('status', ['pago', 'parcial'])
            ->whereBetween('data_pagamento', [$inicio, $fim])
            ->selectRaw('categoria, SUM(valor_pago) as total')
            ->groupBy('categoria')
            ->orderByDesc('total')
            ->pluck('total', 'categoria');

        // ── Top 10 melhores dias ──
        $topMelhores = CaixaDiario::where('loja_id', $lojaId)
            ->whereBetween('data', [$inicio, $fim])
            ->where('total_entradas', '>', 0)
            ->orderByDesc('total_entradas')
            ->limit(10)
            ->get(['data', 'total_entradas', 'total_saidas', 'saldo']);

        // ── Top 10 piores dias ──
        $topPiores = CaixaDiario::where('loja_id', $lojaId)
            ->whereBetween('data', [$inicio, $fim])
            ->where('total_entradas', '>', 0)
            ->orderBy('total_entradas')
            ->limit(10)
            ->get(['data', 'total_entradas', 'total_saidas', 'saldo']);

        // ── Top 5 maiores despesas ──
        $maioresDespesas = Pagamento::with('fornecedor')
            ->where('loja_id', $lojaId)
            ->whereIn('status', ['pago', 'parcial'])
            ->whereBetween('data_pagamento', [$inicio, $fim])
            ->orderByDesc('valor_pago')
            ->limit(5)
            ->get(['descricao', 'categoria', 'valor_pago', 'data_pagamento', 'fornecedor_id']);

        // ── Pagamentos proximos (7 dias) ──
        $pagamentosProximos = Pagamento::with('fornecedor')
            ->where('loja_id', $lojaId)
            ->whereIn('status', ['pendente', 'atrasado', 'parcial'])
            ->where('data_vencimento', '<=', Carbon::now()->addDays(7))
            ->orderBy('data_vencimento')
            ->limit(10)
            ->get();

        // ── Produtos com estoque baixo ──
        $produtosEstoqueBaixo = Produto::with('fornecedor')
            ->where('loja_id', $lojaId)
            ->where('ativo', true)
            ->whereNotNull('estoque_min')
            ->whereColumn('estoque_atual', '<=', 'estoque_min')
            ->orderBy('estoque_atual')
            ->limit(10)
            ->get(['id', 'nome', 'categoria', 'estoque_atual', 'estoque_min', 'fornecedor_id']);

        return response()->json([
            // Cards
            'total_entradas' => (float) $totalEntradas,
            'total_saidas' => (float) $totalSaidas,
            'saldo' => (float) $saldo,
            'caixas_abertos' => $caixasAbertos,
            'pagamentos_pendentes' => $pagamentosPendentes,
            'pagamentos_atrasados' => $pagamentosAtrasados,
            'estoque_baixo' => $estoqueBaixo,

            // Comparativo
            'entradas_anterior' => (float) $entradasAnterior,
            'saidas_anterior' => (float) $saidasAnterior,

            // Graficos
            'grafico' => $grafico,
            'entradas_por_forma' => $entradasPorForma,
            'saidas_por_categoria' => $saidasPorCategoria,

            // Tabelas
            'top_melhores' => $topMelhores,
            'top_piores' => $topPiores,
            'maiores_despesas' => $maioresDespesas,
            'pagamentos_proximos' => $pagamentosProximos,
            'produtos_estoque_baixo' => $produtosEstoqueBaixo,
        ]);
    }
}
