<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MovimentacaoInternaRequest;
use App\Models\MovimentacaoInterna;
use Illuminate\Http\Request;

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
}
