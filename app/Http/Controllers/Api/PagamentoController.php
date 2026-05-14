<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PagamentoRequest;
use App\Models\Pagamento;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PagamentoController extends Controller
{
    public function index(Request $request)
    {
        $lojaId = auth()->user()->loja_id;

        $query = Pagamento::with(['fornecedor', 'banco'])
            ->where('loja_id', $lojaId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('fornecedor_id')) {
            $query->where('fornecedor_id', $request->fornecedor_id);
        }

        if ($request->filled('data_inicio')) {
            $query->where('data_vencimento', '>=', $request->data_inicio);
        }

        if ($request->filled('data_fim')) {
            $query->where('data_vencimento', '<=', $request->data_fim);
        }

        // Atualizar atrasados automaticamente
        Pagamento::where('loja_id', $lojaId)
            ->where('status', 'pendente')
            ->where('data_vencimento', '<', Carbon::today())
            ->update(['status' => 'atrasado']);

        $semPaginacao = $request->boolean('sem_paginacao');
        $perPage = min((int) $request->input('per_page', 20), 200);

        $base = (clone $query);
        $totalGeral = (float) (clone $base)->sum('valor_total');
        $totalPago = (float) (clone $base)->sum('valor_pago');

        $totais = [
            'total_geral' => $totalGeral,
            'total_pago' => $totalPago,
            'total_pendente' => $totalGeral - $totalPago,
            'count' => (clone $base)->count(),
        ];

        if ($semPaginacao) {
            $lista = $query->orderBy('data_vencimento')->get();

            return response()->json([
                'data' => $lista,
                'totais' => $totais,
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $lista->count(),
                'total' => $lista->count(),
            ]);
        }

        $paginated = $query->orderBy('data_vencimento')->paginate($perPage)->toArray();
        $paginated['totais'] = $totais;

        return response()->json($paginated);
    }

    public function store(PagamentoRequest $request)
    {
        $data = $request->validated();
        $quantidadeParcelas = max(1, (int) ($data['quantidade_parcelas'] ?? 1));
        $recorrenciaDias = max(1, (int) ($data['recorrencia_dias'] ?? 30));
        $dataPrimeiroPagamento = $data['data_primeiro_pagamento'] ?? $data['data_vencimento'];
        $parcelasLote = collect($data['parcelas_lote'] ?? []);

        unset($data['quantidade_parcelas'], $data['recorrencia_dias'], $data['data_primeiro_pagamento'], $data['parcelas_lote']);

        $data['loja_id'] = auth()->user()->loja_id;
        $data['status'] = 'pendente';

        if ($quantidadeParcelas <= 1) {
            $pagamento = Pagamento::create($data);
            return response()->json($pagamento->load(['fornecedor', 'banco']), 201);
        }

        if ($parcelasLote->isNotEmpty() && $parcelasLote->count() !== $quantidadeParcelas) {
            return response()->json(['message' => 'A quantidade de parcelas no lote nao confere com a quantidade selecionada.'], 422);
        }

        $valorTotalCentavos = (int) round(((float) $data['valor_total']) * 100);
        $parcelasConfiguradas = $parcelasLote->isNotEmpty()
            ? $parcelasLote->values()->map(function ($parcela, $idx) {
                return [
                    'numero' => $idx + 1,
                    'data_vencimento' => Carbon::parse($parcela['data_vencimento'])->toDateString(),
                    'valor_total' => (float) $parcela['valor_total'],
                ];
            })
            : $this->gerarParcelasPadrao($quantidadeParcelas, $recorrenciaDias, $dataPrimeiroPagamento, $valorTotalCentavos);

        $totalParcelasCentavos = (int) round($parcelasConfiguradas->sum(function ($parcela) {
            return ((float) $parcela['valor_total']) * 100;
        }));

        if ($totalParcelasCentavos !== $valorTotalCentavos) {
            return response()->json(['message' => 'A soma dos valores das parcelas deve ser igual ao valor total informado.'], 422);
        }

        $descricaoBase = $data['descricao'];

        $pagamentosCriados = collect();

        foreach ($parcelasConfiguradas as $parcela) {
            $item = $data;
            $item['descricao'] = $descricaoBase . ' (' . $parcela['numero'] . '/' . $quantidadeParcelas . ')';
            $item['valor_total'] = (float) $parcela['valor_total'];
            $item['recorrente'] = false;
            $item['dia_recorrencia'] = null;
            $item['data_vencimento'] = $parcela['data_vencimento'];

            $pagamentosCriados->push(Pagamento::create($item));
        }

        $pagamentosIds = $pagamentosCriados->pluck('id')->all();
        $pagamentosComRelacoes = Pagamento::with(['fornecedor', 'banco'])
            ->whereIn('id', $pagamentosIds)
            ->orderBy('id')
            ->get();

        return response()->json([
            'parcelado' => true,
            'total_parcelas' => $quantidadeParcelas,
            'pagamentos' => $pagamentosComRelacoes,
        ], 201);
    }

    private function gerarParcelasPadrao(int $quantidadeParcelas, int $recorrenciaDias, string $dataPrimeiroPagamento, int $valorTotalCentavos)
    {
        $baseCentavos = intdiv($valorTotalCentavos, $quantidadeParcelas);
        $restoCentavos = $valorTotalCentavos % $quantidadeParcelas;
        $dataInicial = Carbon::parse($dataPrimeiroPagamento)->startOfDay();

        $parcelas = collect();
        for ($i = 0; $i < $quantidadeParcelas; $i++) {
            $valorParcelaCentavos = $baseCentavos + ($i < $restoCentavos ? 1 : 0);
            $parcelas->push([
                'numero' => $i + 1,
                'valor_total' => $valorParcelaCentavos / 100,
                'data_vencimento' => (clone $dataInicial)->addDays($i * $recorrenciaDias)->toDateString(),
            ]);
        }

        return $parcelas;
    }

    public function show(Pagamento $pagamento)
    {
        return response()->json($pagamento->load(['fornecedor', 'banco']));
    }

    public function update(PagamentoRequest $request, Pagamento $pagamento)
    {
        if ($pagamento->status === 'pago') {
            return response()->json(['message' => 'Pagamento ja quitado nao pode ser editado.'], 422);
        }

        $pagamento->update($request->validated());

        return response()->json($pagamento->load(['fornecedor', 'banco']));
    }

    public function destroy(Pagamento $pagamento)
    {
        $pagamento->delete();
        return response()->json(null, 204);
    }

    public function registrarPagamento(Request $request, Pagamento $pagamento)
    {
        $request->validate([
            'valor_pago' => ['required', 'numeric', 'min:0.01'],
            'forma_pagamento' => ['required', 'in:dinheiro,pix,boleto,transferencia'],
            'banco_id' => ['nullable', 'exists:bancos,id'],
            'data_pagamento' => ['required', 'date'],
        ]);

        $novoValorPago = $pagamento->valor_pago + $request->valor_pago;

        if ($novoValorPago >= $pagamento->valor_total) {
            $pagamento->update([
                'valor_pago' => $pagamento->valor_total,
                'status' => 'pago',
                'forma_pagamento' => $request->forma_pagamento,
                'banco_id' => $request->banco_id,
                'data_pagamento' => $request->data_pagamento,
            ]);
        } else {
            $pagamento->update([
                'valor_pago' => $novoValorPago,
                'status' => 'parcial',
                'forma_pagamento' => $request->forma_pagamento,
                'banco_id' => $request->banco_id,
                'data_pagamento' => $request->data_pagamento,
            ]);
        }

        return response()->json($pagamento->fresh()->load(['fornecedor', 'banco']));
    }

    public function alertas()
    {
        $lojaId = auth()->user()->loja_id;
        $hoje = Carbon::today();
        $em7dias = Carbon::today()->addDays(7);

        // Atualizar atrasados
        Pagamento::where('loja_id', $lojaId)
            ->where('status', 'pendente')
            ->where('data_vencimento', '<', $hoje)
            ->update(['status' => 'atrasado']);

        $atrasados = Pagamento::with('fornecedor')
            ->where('loja_id', $lojaId)
            ->where('status', 'atrasado')
            ->orderBy('data_vencimento')
            ->get();

        $proximos = Pagamento::with('fornecedor')
            ->where('loja_id', $lojaId)
            ->where('status', 'pendente')
            ->whereBetween('data_vencimento', [$hoje, $em7dias])
            ->orderBy('data_vencimento')
            ->get();

        return response()->json([
            'atrasados' => $atrasados,
            'proximos' => $proximos,
            'total_atrasados' => $atrasados->count(),
            'total_proximos' => $proximos->count(),
        ]);
    }
}
