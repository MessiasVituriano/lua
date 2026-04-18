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

        $perPage = min((int) $request->input('per_page', 20), 200);

        return response()->json(
            $query->orderBy('data_vencimento')->paginate($perPage)
        );
    }

    public function store(PagamentoRequest $request)
    {
        $data = $request->validated();
        $data['loja_id'] = auth()->user()->loja_id;
        $data['status'] = 'pendente';

        $pagamento = Pagamento::create($data);

        return response()->json($pagamento->load(['fornecedor', 'banco']), 201);
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
