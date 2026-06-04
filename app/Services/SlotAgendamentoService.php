<?php

namespace App\Services;

use App\Models\BanhoTosaAgendamento;
use App\Models\BanhoTosaServico;
use App\Models\CalendarioFuncionamento;
use App\Models\ExcecaoFuncionamento;
use Carbon\Carbon;

class SlotAgendamentoService
{
    private const DIA_SEMANA = [
        0 => 'domingo',
        1 => 'segunda',
        2 => 'terca',
        3 => 'quarta',
        4 => 'quinta',
        5 => 'sexta',
        6 => 'sabado',
    ];

    /**
     * Retorna os próximos dias disponíveis para agendamento, com horários de funcionamento.
     */
    public function diasDisponiveis(int $lojaId, int $diasAdiantamento = 14): array
    {
        $hoje   = Carbon::today();
        $inicio = $hoje->copy()->addDay();
        $fim    = $hoje->copy()->addDays($diasAdiantamento);

        // Busca exceções e calendário de uma vez só
        $excecoes = ExcecaoFuncionamento::where('loja_id', $lojaId)
            ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
            ->get()
            ->keyBy(fn($e) => $e->data->toDateString());

        $calendarios = CalendarioFuncionamento::where('loja_id', $lojaId)
            ->get()
            ->keyBy('dia_semana');

        $dias = [];

        for ($i = 1; $i <= $diasAdiantamento; $i++) {
            $data      = $hoje->copy()->addDays($i);
            $dataStr   = $data->toDateString();
            $diaSemana = self::DIA_SEMANA[$data->dayOfWeek];

            $excecao    = $excecoes->get($dataStr);
            $calendario = $calendarios->get($diaSemana);

            if ($excecao?->tipo === 'fechado') {
                continue;
            }

            if (!$calendario?->ativa && $excecao?->tipo !== 'aberto') {
                continue;
            }

            $dias[] = [
                'data'               => $dataStr,
                'dia_semana'         => $diaSemana,
                'horario_abertura'   => $calendario?->horario_abertura  ?? '08:00:00',
                'horario_fechamento' => $calendario?->horario_fechamento ?? '18:00:00',
            ];
        }

        return $dias;
    }

    /**
     * Retorna os slots de horário livres para um dia e serviço específicos.
     */
    public function slotsDisponiveis(int $lojaId, string $data, BanhoTosaServico $servico): array
    {
        $carbon    = Carbon::parse($data);
        $diaSemana = self::DIA_SEMANA[$carbon->dayOfWeek];

        $excecao = ExcecaoFuncionamento::where('loja_id', $lojaId)
            ->where('data', $data)
            ->first();

        if ($excecao?->tipo === 'fechado') {
            return [];
        }

        $calendario = CalendarioFuncionamento::where('loja_id', $lojaId)
            ->where('dia_semana', $diaSemana)
            ->first();

        if (!$calendario?->ativa && $excecao?->tipo !== 'aberto') {
            return [];
        }

        $abertura   = $calendario?->horario_abertura  ?? '08:00:00';
        $fechamento = $calendario?->horario_fechamento ?? '18:00:00';
        $duracao    = $servico->duracao_minutos;

        // Gera todos os slots possíveis no intervalo do dia
        $slots = [];
        $atual = Carbon::createFromTimeString($abertura);
        $fim   = Carbon::createFromTimeString($fechamento);

        while ($atual->copy()->addMinutes($duracao)->lte($fim)) {
            $slots[] = $atual->format('H:i');
            $atual->addMinutes($duracao);
        }

        if (empty($slots)) {
            return [];
        }

        // Agendamentos existentes no dia (excluindo cancelados)
        $agendamentos = BanhoTosaAgendamento::where('loja_id', $lojaId)
            ->whereDate('data', $data)
            ->whereNotIn('status', ['cancelado'])
            ->get(['horario_inicio', 'horario_fim']);

        // Remove slots com sobreposição
        return array_values(array_filter($slots, function (string $slot) use ($agendamentos, $duracao) {
            $slotInicio = Carbon::createFromTimeString($slot);
            $slotFim    = $slotInicio->copy()->addMinutes($duracao);

            foreach ($agendamentos as $ag) {
                $agInicio = Carbon::createFromTimeString($ag->horario_inicio);
                $agFim    = Carbon::createFromTimeString($ag->horario_fim);

                // Sobreposição: slot começa antes do fim do agendamento E termina depois do início
                if ($slotInicio->lt($agFim) && $slotFim->gt($agInicio)) {
                    return false;
                }
            }

            return true;
        }));
    }
}
