<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MovimentacaoInternaRequest;
use App\Models\Banco;
use App\Models\EntradaCaixa;
use App\Models\MovimentacaoInterna;
use App\Models\Pagamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MovimentacaoInternaController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin')->only(['aprovar', 'rejeitar']);
    }

    public function index(Request $request)
    {
        $lojaId = auth()->user()->loja_id;

        $query = MovimentacaoInterna::with(['bancoOrigem', 'bancoDestino', 'lojaDestino', 'solicitadoPor', 'aprovadoPor'])
            ->where('loja_id', $lojaId);

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('data_inicio')) {
            $query->where('data_movimentacao', '>=', $request->data_inicio);
        }

        if ($request->filled('data_fim')) {
            $query->where('data_movimentacao', '<=', $request->data_fim);
        }

        return response()->json(
            $query->orderByDesc('data_movimentacao')->orderByDesc('id')->paginate(20)
        );
    }

    public function store(MovimentacaoInternaRequest $request)
    {
        $user = auth()->user();
        $data = $request->validated();
        $data['loja_id'] = $user->loja_id;
        $data['solicitado_por'] = $user->id;

        // Admin cria ja como aprovada
        if ($user->isAdmin()) {
            $data['status'] = 'aprovada';
            $data['aprovado_por'] = $user->id;
            $data['aprovado_em'] = now();
        } else {
            $data['status'] = 'solicitada';
        }

        $movimentacao = MovimentacaoInterna::create($data);

        return response()->json(
            $movimentacao->load(['bancoOrigem', 'bancoDestino', 'lojaDestino', 'solicitadoPor', 'aprovadoPor']),
            201
        );
    }

    public function show(MovimentacaoInterna $movimentacoes_interna)
    {
        return response()->json(
            $movimentacoes_interna->load(['bancoOrigem', 'bancoDestino', 'lojaDestino', 'solicitadoPor', 'aprovadoPor'])
        );
    }

    public function update(MovimentacaoInternaRequest $request, MovimentacaoInterna $movimentacoes_interna)
    {
        if ($movimentacoes_interna->status !== 'solicitada') {
            return response()->json(['message' => 'Somente movimentacoes com status "solicitada" podem ser editadas.'], 422);
        }

        $movimentacoes_interna->update($request->validated());

        return response()->json(
            $movimentacoes_interna->load(['bancoOrigem', 'bancoDestino', 'lojaDestino', 'solicitadoPor', 'aprovadoPor'])
        );
    }

    public function destroy(MovimentacaoInterna $movimentacoes_interna)
    {
        if ($movimentacoes_interna->status === 'aprovada') {
            return response()->json(['message' => 'Movimentacoes aprovadas nao podem ser removidas.'], 422);
        }

        $movimentacoes_interna->delete();
        return response()->json(null, 204);
    }

    public function aprovar(MovimentacaoInterna $movimentacoes_interna)
    {
        if ($movimentacoes_interna->status !== 'solicitada') {
            return response()->json(['message' => 'Movimentacao nao esta pendente de aprovacao.'], 422);
        }

        $movimentacoes_interna->update([
            'status' => 'aprovada',
            'aprovado_por' => auth()->id(),
            'aprovado_em' => now(),
        ]);

        return response()->json(
            $movimentacoes_interna->fresh()->load(['bancoOrigem', 'bancoDestino', 'lojaDestino', 'solicitadoPor', 'aprovadoPor'])
        );
    }

    public function rejeitar(Request $request, MovimentacaoInterna $movimentacoes_interna)
    {
        $request->validate([
            'motivo_rejeicao' => ['required', 'string', 'max:255'],
        ]);

        if ($movimentacoes_interna->status !== 'solicitada') {
            return response()->json(['message' => 'Movimentacao nao esta pendente de aprovacao.'], 422);
        }

        $movimentacoes_interna->update([
            'status' => 'rejeitada',
            'aprovado_por' => auth()->id(),
            'aprovado_em' => now(),
            'motivo_rejeicao' => $request->motivo_rejeicao,
        ]);

        return response()->json(
            $movimentacoes_interna->fresh()->load(['bancoOrigem', 'bancoDestino', 'lojaDestino', 'solicitadoPor', 'aprovadoPor'])
        );
    }

    public function pendentes()
    {
        $lojaId = auth()->user()->loja_id;

        $pendentes = MovimentacaoInterna::with(['solicitadoPor', 'bancoOrigem', 'bancoDestino', 'lojaDestino'])
            ->where('loja_id', $lojaId)
            ->where('status', 'solicitada')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($pendentes);
    }

    public function saldos()
    {
        $lojaId = auth()->user()->loja_id;

        $bancos = Banco::orderBy('nome')->get(['id', 'nome', 'ativo']);

        // Movimentacoes aprovadas: entradas e saidas por banco
        $movEntradasPorBanco = MovimentacaoInterna::query()
            ->where('loja_id', $lojaId)
            ->where('status', 'aprovada')
            ->whereIn('tipo', ['aporte', 'transferencia_banco'])
            ->whereNotNull('banco_destino_id')
            ->groupBy('banco_destino_id')
            ->pluck(DB::raw('SUM(valor)'), 'banco_destino_id');

        $movSaidasPorBanco = MovimentacaoInterna::query()
            ->where('loja_id', $lojaId)
            ->where('status', 'aprovada')
            ->whereIn('tipo', ['sangria', 'transferencia_banco'])
            ->whereNotNull('banco_origem_id')
            ->groupBy('banco_origem_id')
            ->pluck(DB::raw('SUM(valor)'), 'banco_origem_id');

        // Entradas de caixa creditadas em banco (cartao, pix com banco)
        $entradasCaixaPorBanco = EntradaCaixa::query()
            ->whereHas('caixaDiario', fn ($q) => $q->where('loja_id', $lojaId))
            ->whereNotNull('banco_id')
            ->groupBy('banco_id')
            ->pluck(DB::raw('SUM(valor)'), 'banco_id');

        // Pagamentos efetuados por banco
        $pagamentosPorBanco = Pagamento::query()
            ->where('loja_id', $lojaId)
            ->whereIn('status', ['pago', 'parcial'])
            ->whereNotNull('banco_id')
            ->groupBy('banco_id')
            ->pluck(DB::raw('SUM(valor_pago)'), 'banco_id');

        $saldosBancos = $bancos->map(function ($banco) use ($movEntradasPorBanco, $movSaidasPorBanco, $entradasCaixaPorBanco, $pagamentosPorBanco) {
            $entradas = (float) ($movEntradasPorBanco[$banco->id] ?? 0)
                + (float) ($entradasCaixaPorBanco[$banco->id] ?? 0);
            $saidas = (float) ($movSaidasPorBanco[$banco->id] ?? 0)
                + (float) ($pagamentosPorBanco[$banco->id] ?? 0);

            return [
                'id' => $banco->id,
                'nome' => $banco->nome,
                'ativo' => (bool) $banco->ativo,
                'entradas' => round($entradas, 2),
                'saidas' => round($saidas, 2),
                'saldo' => round($entradas - $saidas, 2),
            ];
        });

        // Caixa dinheiro fisico
        $entradasDinheiro = (float) EntradaCaixa::query()
            ->whereHas('caixaDiario', fn ($q) => $q->where('loja_id', $lojaId))
            ->where('forma_recebimento', 'dinheiro')
            ->sum('valor');

        $pagamentosDinheiro = (float) Pagamento::query()
            ->where('loja_id', $lojaId)
            ->whereIn('status', ['pago', 'parcial'])
            ->where('forma_pagamento', 'dinheiro')
            ->sum('valor_pago');

        $aportesDinheiro = (float) MovimentacaoInterna::query()
            ->where('loja_id', $lojaId)
            ->where('status', 'aprovada')
            ->where('tipo', 'aporte')
            ->whereNull('banco_destino_id')
            ->sum('valor');

        $sangriasDinheiro = (float) MovimentacaoInterna::query()
            ->where('loja_id', $lojaId)
            ->where('status', 'aprovada')
            ->where('tipo', 'sangria')
            ->whereNull('banco_origem_id')
            ->sum('valor');

        // Transferencias entre lojas saindo
        $transfLojaSaida = (float) MovimentacaoInterna::query()
            ->where('loja_id', $lojaId)
            ->where('status', 'aprovada')
            ->where('tipo', 'transferencia_loja')
            ->sum('valor');

        $transfLojaEntrada = (float) MovimentacaoInterna::query()
            ->where('loja_destino_id', $lojaId)
            ->where('status', 'aprovada')
            ->where('tipo', 'transferencia_loja')
            ->sum('valor');

        $entradasCaixa = round($entradasDinheiro + $aportesDinheiro + $transfLojaEntrada, 2);
        $saidasCaixa = round($pagamentosDinheiro + $sangriasDinheiro + $transfLojaSaida, 2);

        $caixaDinheiro = [
            'entradas' => $entradasCaixa,
            'saidas' => $saidasCaixa,
            'saldo' => round($entradasCaixa - $saidasCaixa, 2),
        ];

        $totalSaldo = round($saldosBancos->sum('saldo') + $caixaDinheiro['saldo'], 2);

        return response()->json([
            'bancos' => $saldosBancos->values(),
            'caixa_dinheiro' => $caixaDinheiro,
            'total' => $totalSaldo,
        ]);
    }
}
