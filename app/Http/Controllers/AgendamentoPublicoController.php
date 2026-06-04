<?php

namespace App\Http\Controllers;

use App\Models\BanhoTosaAgendamento;
use App\Models\BanhoTosaServico;
use App\Models\LinkAgendamento;
use App\Services\SlotAgendamentoService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AgendamentoPublicoController extends Controller
{
    public function __construct(private readonly SlotAgendamentoService $slotService)
    {
    }

    /**
     * GET /api/publico/agendar/{token}
     * Valida o token e retorna dados para montar a página de agendamento.
     */
    public function show(string $token)
    {
        $link = $this->resolveLink($token);

        $servicos = BanhoTosaServico::where('loja_id', $link->loja_id)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get(['id', 'nome', 'categoria', 'preco_base', 'duracao_minutos']);

        $dias = $this->slotService->diasDisponiveis($link->loja_id);

        return response()->json([
            'loja'     => $link->loja()->first(['id', 'nome', 'telefone']),
            'cliente'  => $link->cliente?->load('pets'),
            'servicos' => $servicos,
            'dias'     => $dias,
        ]);
    }

    /**
     * GET /api/publico/agendar/{token}/slots?data=YYYY-MM-DD&servico_id=X
     * Retorna os horários livres para o dia e serviço escolhidos.
     */
    public function slots(Request $request, string $token)
    {
        $request->validate([
            'data'       => ['required', 'date', 'after_or_equal:today'],
            'servico_id' => ['required', 'integer'],
        ]);

        $link = $this->resolveLink($token);

        $servico = BanhoTosaServico::where('loja_id', $link->loja_id)
            ->where('ativo', true)
            ->findOrFail($request->servico_id);

        $slots = $this->slotService->slotsDisponiveis($link->loja_id, $request->data, $servico);

        return response()->json(['slots' => $slots]);
    }

    /**
     * POST /api/publico/agendar/{token}
     * Confirma o agendamento e marca o link como usado.
     */
    public function store(Request $request, string $token)
    {
        $link = $this->resolveLink($token);

        $dados = $request->validate([
            'servico_id'     => ['required', 'integer'],
            'pet_id'         => ['nullable', 'integer'],
            'data'           => ['required', 'date', 'after_or_equal:today'],
            'horario_inicio' => ['required', 'date_format:H:i'],
            'observacao'     => ['nullable', 'string', 'max:500'],
        ]);

        $servico = BanhoTosaServico::where('loja_id', $link->loja_id)
            ->where('ativo', true)
            ->findOrFail($dados['servico_id']);

        // Verifica novamente se o slot ainda está disponível (evita race condition)
        $slotsLivres = $this->slotService->slotsDisponiveis($link->loja_id, $dados['data'], $servico);
        if (!in_array($dados['horario_inicio'], $slotsLivres)) {
            return response()->json(['message' => 'Este horário não está mais disponível. Por favor, escolha outro.'], 422);
        }

        $horarioFim = Carbon::createFromTimeString($dados['horario_inicio'])
            ->addMinutes($servico->duracao_minutos)
            ->format('H:i:s');

        $agendamento = BanhoTosaAgendamento::create([
            'loja_id'        => $link->loja_id,
            'cliente_id'     => $link->cliente_id,
            'pet_id'         => $dados['pet_id'] ?? null,
            'servico_id'     => $servico->id,
            'data'           => $dados['data'],
            'horario_inicio' => $dados['horario_inicio'],
            'horario_fim'    => $horarioFim,
            'valor_estimado' => $servico->preco_base,
            'status'         => 'solicitado',
            'observacao'     => $dados['observacao'] ?? null,
        ]);

        $link->update(['usado_em' => now()]);

        return response()->json([
            'message' => 'Agendamento realizado com sucesso!',
            'agendamento' => [
                'data'           => $agendamento->data->format('d/m/Y'),
                'horario_inicio' => substr($agendamento->horario_inicio, 0, 5),
                'horario_fim'    => substr($agendamento->horario_fim, 0, 5),
                'servico'        => $servico->nome,
            ],
        ], 201);
    }

    private function resolveLink(string $token): LinkAgendamento
    {
        $link = LinkAgendamento::where('token', $token)
            ->with(['loja', 'cliente.pets'])
            ->first();

        if (!$link || !$link->estaValido()) {
            abort(404, 'Link inválido ou expirado.');
        }

        return $link;
    }
}
