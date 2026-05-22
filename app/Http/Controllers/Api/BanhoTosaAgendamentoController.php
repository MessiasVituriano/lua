<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BanhoTosaAgendamento;
use App\Models\BanhoTosaServico;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BanhoTosaAgendamentoController extends Controller
{
    public function index(Request $request)
    {
        $lojaId = auth()->user()->loja_id;

        // Modo mensal: retorna agendamentos do mês agrupados por dia
        if ($request->filled('mes') && $request->filled('ano')) {
            $inicio = Carbon::create($request->ano, $request->mes, 1)->startOfMonth();
            $fim    = $inicio->copy()->endOfMonth();

            $agendamentos = BanhoTosaAgendamento::query()
                ->where('loja_id', $lojaId)
                ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
                ->with(['cliente:id,nome', 'pet:id,nome,porte', 'servico:id,nome,categoria'])
                ->orderBy('data')
                ->orderBy('horario_inicio')
                ->get();

            $grouped = $agendamentos->groupBy(fn($a) => $a->data->toDateString())
                ->map(fn($items) => $items->values());

            return response()->json(['data' => $grouped]);
        }

        // Modo diário: retorna agendamentos de uma data específica + resumo
        $data = $request->filled('data') ? $request->data : now()->toDateString();

        $query = BanhoTosaAgendamento::query()
            ->where('loja_id', $lojaId)
            ->whereDate('data', $data)
            ->with(['cliente:id,nome', 'pet:id,nome,porte', 'servico:id,nome,categoria']);

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->whereHas('cliente', fn($cq) => $cq->where('nome', 'ilike', "%$busca%"))
                    ->orWhereHas('pet', fn($pq) => $pq->where('nome', 'ilike', "%$busca%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $items = $query->orderBy('horario_inicio')->get();

        $concluidos = $items->where('status', 'concluido');

        $resumo = [
            'total'       => $items->count(),
            'concluidos'  => $concluidos->count(),
            'pendentes'   => $items->whereNotIn('status', ['concluido', 'cancelado', 'faltou'])->count(),
            'faturamento' => $concluidos->sum('valor_final'),
        ];

        return response()->json(['data' => $items->values(), 'resumo' => $resumo]);
    }

    public function store(Request $request)
    {
        $lojaId = auth()->user()->loja_id;

        $dados = $request->validate([
            'cliente_id'     => ['nullable', 'integer', 'exists:clientes,id'],
            'pet_id'         => ['nullable', 'integer', 'exists:pets,id'],
            'servico_id'     => ['nullable', 'integer', 'exists:banho_tosa_servicos,id'],
            'data'           => ['required', 'date'],
            'horario_inicio' => ['required', 'date_format:H:i,H:i:s'],
            'horario_fim'    => ['required', 'date_format:H:i,H:i:s', 'after:horario_inicio'],
            'valor_estimado' => ['nullable', 'numeric', 'min:0'],
            'observacao'     => ['nullable', 'string', 'max:1000'],
        ]);

        $ag = BanhoTosaAgendamento::create(array_merge($dados, [
            'loja_id' => $lojaId,
            'status'  => 'solicitado',
        ]));

        return response()->json($ag->load(['cliente:id,nome', 'pet:id,nome,porte', 'servico:id,nome']), 201);
    }

    public function update(Request $request, BanhoTosaAgendamento $agendamento)
    {
        $this->checkLoja($agendamento);

        $dados = $request->validate([
            'cliente_id'     => ['nullable', 'integer', 'exists:clientes,id'],
            'pet_id'         => ['nullable', 'integer', 'exists:pets,id'],
            'servico_id'     => ['nullable', 'integer', 'exists:banho_tosa_servicos,id'],
            'data'           => ['sometimes', 'date'],
            'horario_inicio' => ['sometimes', 'date_format:H:i,H:i:s'],
            'horario_fim'    => ['sometimes', 'date_format:H:i,H:i:s'],
            'valor_estimado' => ['nullable', 'numeric', 'min:0'],
            'valor_final'    => ['nullable', 'numeric', 'min:0'],
            'observacao'     => ['nullable', 'string', 'max:1000'],
        ]);

        $agendamento->update($dados);

        return response()->json($agendamento->load(['cliente:id,nome', 'pet:id,nome,porte', 'servico:id,nome']));
    }

    public function confirmar(BanhoTosaAgendamento $agendamento)
    {
        $this->checkLoja($agendamento);
        $agendamento->update(['status' => 'confirmado']);
        return response()->json(['status' => $agendamento->status]);
    }

    public function iniciar(BanhoTosaAgendamento $agendamento)
    {
        $this->checkLoja($agendamento);
        $agendamento->update(['status' => 'em_andamento']);
        return response()->json(['status' => $agendamento->status]);
    }

    public function concluir(Request $request, BanhoTosaAgendamento $agendamento)
    {
        $this->checkLoja($agendamento);
        $dados = $request->validate([
            'valor_final' => ['nullable', 'numeric', 'min:0'],
        ]);
        $agendamento->update(array_merge($dados, ['status' => 'concluido']));
        return response()->json(['status' => $agendamento->status]);
    }

    public function cancelar(BanhoTosaAgendamento $agendamento)
    {
        $this->checkLoja($agendamento);
        $agendamento->update(['status' => 'cancelado']);
        return response()->json(['status' => $agendamento->status]);
    }

    private function checkLoja(BanhoTosaAgendamento $agendamento): void
    {
        abort_unless($agendamento->loja_id === auth()->user()->loja_id, 403);
    }
}
