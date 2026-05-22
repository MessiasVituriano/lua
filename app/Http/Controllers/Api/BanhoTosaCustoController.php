<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BanhoTosaAgendamento;
use App\Models\BanhoTosaCusto;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BanhoTosaCustoController extends Controller
{
    public function index(Request $request)
    {
        $lojaId     = auth()->user()->loja_id;
        $dataInicio = $request->filled('data_inicio')
            ? $request->data_inicio
            : now()->startOfMonth()->toDateString();
        $dataFim    = $request->filled('data_fim')
            ? $request->data_fim
            : now()->endOfMonth()->toDateString();

        $query = BanhoTosaCusto::query()
            ->where('loja_id', $lojaId)
            ->whereBetween('data_custo', [$dataInicio, $dataFim])
            ->with('servico:id,nome');

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $custos = $query->orderByDesc('data_custo')->get();

        $totalCustos = $custos->sum('valor');

        // Faturamento dos atendimentos concluídos no período
        $faturamento = BanhoTosaAgendamento::query()
            ->where('loja_id', $lojaId)
            ->whereBetween('data', [$dataInicio, $dataFim])
            ->where('status', 'concluido')
            ->sum('valor_final');

        $totalAtendimentos = BanhoTosaAgendamento::query()
            ->where('loja_id', $lojaId)
            ->whereBetween('data', [$dataInicio, $dataFim])
            ->where('status', 'concluido')
            ->count();

        $margem = $faturamento > 0
            ? (($faturamento - $totalCustos) / $faturamento) * 100
            : 0;

        $ticketMedio = $totalAtendimentos > 0
            ? $faturamento / $totalAtendimentos
            : 0;

        return response()->json([
            'data'   => $custos->values(),
            'totais' => [
                'custos'       => round($totalCustos, 2),
                'faturamento'  => round($faturamento, 2),
                'margem'       => round($margem, 2),
                'ticket_medio' => round($ticketMedio, 2),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'servico_id'  => ['nullable', 'integer', 'exists:banho_tosa_servicos,id'],
            'descricao'   => ['required', 'string', 'max:255'],
            'tipo'        => ['required', 'in:fixo,variavel,recorrente,insumo,comissao'],
            'valor'       => ['required', 'numeric', 'min:0'],
            'data_custo'  => ['required', 'date'],
            'observacao'  => ['nullable', 'string', 'max:1000'],
        ]);

        $custo = BanhoTosaCusto::create(array_merge($dados, [
            'loja_id' => auth()->user()->loja_id,
            'origem'  => 'manual',
        ]));

        return response()->json($custo, 201);
    }

    public function destroy(BanhoTosaCusto $custo)
    {
        abort_unless($custo->loja_id === auth()->user()->loja_id, 403);
        $custo->delete();
        return response()->json(null, 204);
    }
}
