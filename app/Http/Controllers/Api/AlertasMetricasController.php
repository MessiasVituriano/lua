<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EntradaCaixaItem;
use App\Models\Produto;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlertasMetricasController extends Controller
{
    public function index(Request $request)
    {
        $lojaId = auth()->user()->loja_id;

        $inicio = $request->input('data_inicio', Carbon::now()->startOfMonth()->toDateString());
        $fim = $request->input('data_fim', Carbon::now()->endOfMonth()->toDateString());

        $hoje = Carbon::today()->toDateString();
        $em3dias = Carbon::today()->addDays(3)->toDateString();

        $itensPeriodo = DB::table('entrada_caixa_itens as i')
            ->join('entradas_caixa as e', 'e.id', '=', 'i.entrada_caixa_id')
            ->join('caixas_diarios as c', 'c.id', '=', 'e.caixa_diario_id')
            ->where('c.loja_id', $lojaId)
            ->whereBetween('c.data', [$inicio, $fim]);

        $faturamentoPorPerfil = (clone $itensPeriodo)
            ->selectRaw("COALESCE(i.perfil_pet_tipo, 'outros') as perfil_pet_tipo, SUM(i.subtotal) as total")
            ->groupBy('perfil_pet_tipo')
            ->orderByDesc('total')
            ->get();

        $resumoClientes = (clone $itensPeriodo)
            ->whereNotNull('i.cliente_id')
            ->selectRaw('COALESCE(SUM(i.subtotal), 0) as total, COUNT(DISTINCT i.cliente_id) as clientes')
            ->first();

        $ticketMedioCliente = 0.0;
        if ($resumoClientes && (int) $resumoClientes->clientes > 0) {
            $ticketMedioCliente = (float) $resumoClientes->total / (int) $resumoClientes->clientes;
        }

        $topProdutosReceita = (clone $itensPeriodo)
            ->leftJoin('produtos as p', 'p.id', '=', 'i.produto_id')
            ->selectRaw("COALESCE(p.nome, 'Item sem produto') as produto_nome, SUM(i.subtotal) as receita, SUM(i.quantidade) as quantidade")
            ->groupBy('produto_nome')
            ->orderByDesc('receita')
            ->limit(5)
            ->get();

        $volumeRacaoGramas = (clone $itensPeriodo)
            ->join('produtos as p', 'p.id', '=', 'i.produto_id')
            ->where('p.categoria', 'racao')
            ->selectRaw('COALESCE(SUM(i.peso_gramas), 0) as total_gramas')
            ->value('total_gramas');

        $alertasRecompra = EntradaCaixaItem::query()
            ->with(['produto:id,nome', 'pet:id,nome,cliente_id', 'pet.cliente:id,nome,telefone', 'cliente:id,nome,telefone'])
            ->whereNotNull('data_proxima_compra_estimada')
            ->whereBetween('data_proxima_compra_estimada', [$hoje, $em3dias])
            ->whereHas('entradaCaixa.caixaDiario', function ($query) use ($lojaId) {
                $query->where('loja_id', $lojaId);
            })
            ->orderBy('data_proxima_compra_estimada')
            ->limit(20)
            ->get();

        $totalRecompraAtrasada = EntradaCaixaItem::query()
            ->whereNotNull('data_proxima_compra_estimada')
            ->where('data_proxima_compra_estimada', '<', $hoje)
            ->whereHas('entradaCaixa.caixaDiario', function ($query) use ($lojaId) {
                $query->where('loja_id', $lojaId);
            })
            ->count();

        $alertasEstoqueBaixo = Produto::query()
            ->where('loja_id', $lojaId)
            ->where('ativo', true)
            ->whereNotNull('estoque_min')
            ->whereColumn('estoque_atual', '<=', 'estoque_min')
            ->orderByRaw('(estoque_atual - estoque_min) asc')
            ->limit(20)
            ->get(['id', 'nome', 'categoria', 'estoque_atual', 'estoque_min']);

        return response()->json([
            'periodo' => [
                'data_inicio' => $inicio,
                'data_fim' => $fim,
            ],
            'cards' => [
                'recompras_3_dias' => $alertasRecompra->count(),
                'recompras_atrasadas' => $totalRecompraAtrasada,
                'estoque_baixo' => $alertasEstoqueBaixo->count(),
                'ticket_medio_cliente' => round($ticketMedioCliente, 2),
                'volume_racao_gramas' => (int) $volumeRacaoGramas,
            ],
            'faturamento_por_perfil' => $faturamentoPorPerfil,
            'top_produtos_receita' => $topProdutosReceita,
            'alertas_recompra' => $alertasRecompra,
            'alertas_estoque_baixo' => $alertasEstoqueBaixo,
        ]);
    }
}
