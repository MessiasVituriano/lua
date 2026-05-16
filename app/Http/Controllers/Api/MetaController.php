<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MetaDiaria;
use App\Models\MetaMensal;
use App\Services\MetaService;
use Illuminate\Http\Request;

class MetaController extends Controller
{
    public function __construct(private readonly MetaService $metaService)
    {
        $this->middleware('role:admin');
    }

    public function index(Request $request)
    {
        $lojaId = auth()->user()->loja_id;

        return response()->json($this->metaService->montarResumo(
            $lojaId,
            $request->input('competencia')
        ));
    }

    public function anual(Request $request)
    {
        $dados = $request->validate([
            'ano' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        $ano = (int) ($dados['ano'] ?? now()->year);

        return response()->json($this->metaService->resumoAnual(auth()->user()->loja_id, $ano));
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'tipo' => ['required', 'in:venda,saldo'],
            'competencia' => ['required', 'date'],
            'valor_meta' => ['nullable', 'numeric', 'min:0'],
            'valor_realizado_inicial' => ['nullable', 'numeric', 'min:0'],
            'observacao' => ['nullable', 'string'],
        ]);

        $meta = $this->metaService->upsertMetaMensal(
            auth()->user()->loja_id,
            $dados['tipo'],
            $dados['competencia'],
            (float) ($dados['valor_meta'] ?? 0),
            $dados['observacao'] ?? null,
            isset($dados['valor_realizado_inicial']) ? (float) $dados['valor_realizado_inicial'] : null
        );

        return response()->json($meta->load('diarias'), 201);
    }

    public function update(Request $request, MetaMensal $meta)
    {
        if ($meta->loja_id !== auth()->user()->loja_id) {
            abort(404);
        }

        if ($meta->status === 'fechada') {
            return response()->json(['message' => 'Meta fechada nao pode ser editada.'], 422);
        }

        $dados = $request->validate([
            'valor_meta' => ['required', 'numeric', 'min:0'],
            'valor_realizado_inicial' => ['nullable', 'numeric', 'min:0'],
            'observacao' => ['nullable', 'string'],
            'status' => ['nullable', 'in:aberta,fechada'],
        ]);

        $meta->update($dados);
        $this->metaService->sincronizarCompetencia($meta->loja_id, $meta->competencia);

        return response()->json($meta->fresh('diarias'));
    }

    public function updateDia(Request $request, MetaDiaria $metaDiaria)
    {
        $metaDiaria->load('metaMensal');

        if ($metaDiaria->metaMensal->loja_id !== auth()->user()->loja_id) {
            abort(404);
        }

        $dados = $request->validate([
            'valor_meta' => ['required', 'numeric', 'min:0'],
        ]);

        $meta = $this->metaService->atualizarMetaDiaria($metaDiaria, (float) $dados['valor_meta']);

        return response()->json($meta);
    }

    public function excecao(Request $request)
    {
        $dados = $request->validate([
            'data' => ['required', 'date'],
            'tipo' => ['required', 'in:aberto,fechado'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json(
            $this->metaService->salvarExcecao(
                auth()->user()->loja_id,
                $dados['data'],
                $dados['tipo'],
                $dados['motivo'] ?? null
            ),
            201
        );
    }

    public function fechar(MetaMensal $meta)
    {
        if ($meta->loja_id !== auth()->user()->loja_id) {
            abort(404);
        }

        return response()->json($this->metaService->fecharCompetencia($meta));
    }
}