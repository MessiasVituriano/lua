<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EntradaCaixaRequest;
use App\Models\CaixaDiario;
use App\Models\EntradaCaixa;
use Carbon\Carbon;
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

        $caixa = CaixaDiario::with(['entradas.banco', 'fechadoPor'])
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

    public function abrir()
    {
        $lojaId = auth()->user()->loja_id;
        $hoje = Carbon::today();

        $existente = CaixaDiario::where('loja_id', $lojaId)->where('data', $hoje)->first();
        if ($existente) {
            return response()->json($existente);
        }

        $caixa = CaixaDiario::create([
            'loja_id' => $lojaId,
            'data' => $hoje,
            'status' => 'aberto',
        ]);

        return response()->json($caixa, 201);
    }

    public function adicionarEntrada(EntradaCaixaRequest $request, CaixaDiario $caixa)
    {
        if (in_array($caixa->status, ['fechado', 'pendente'])) {
            return response()->json(['message' => 'Caixa nao esta aberto.'], 422);
        }

        $entrada = $caixa->entradas()->create($request->validated());
        $caixa->recalcular();

        return response()->json($entrada->load('banco'), 201);
    }

    public function removerEntrada(CaixaDiario $caixa, EntradaCaixa $entrada)
    {
        if (in_array($caixa->status, ['fechado', 'pendente'])) {
            return response()->json(['message' => 'Caixa nao esta aberto.'], 422);
        }

        $entrada->delete();
        $caixa->recalcular();

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

        return response()->json($query->paginate(15));
    }

    public function show(CaixaDiario $caixa)
    {
        $caixa->load(['entradas.banco', 'fechadoPor']);

        $totaisPorForma = $caixa->entradas()
            ->selectRaw('forma_recebimento, SUM(valor) as total')
            ->groupBy('forma_recebimento')
            ->pluck('total', 'forma_recebimento');

        return response()->json([
            'caixa' => $caixa,
            'totais_por_forma' => $totaisPorForma,
        ]);
    }
}
