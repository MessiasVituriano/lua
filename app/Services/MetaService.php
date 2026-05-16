<?php

namespace App\Services;

use App\Models\CalendarioFuncionamento;
use App\Models\CaixaDiario;
use App\Models\ExcecaoFuncionamento;
use App\Models\MetaDiaria;
use App\Models\MetaMensal;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class MetaService
{
    public function upsertMetaMensal(
        int $lojaId,
        string $tipo,
        string $competencia,
        float $valorMeta,
        ?string $observacao = null,
        ?float $valorRealizadoInicial = null
    ): MetaMensal
    {
        return DB::transaction(function () use ($lojaId, $tipo, $competencia, $valorMeta, $observacao, $valorRealizadoInicial) {
            $competenciaCarbon = Carbon::parse($competencia)->startOfMonth();

            $metaExistente = MetaMensal::where('loja_id', $lojaId)
                ->where('tipo', $tipo)
                ->whereDate('competencia', $competenciaCarbon->toDateString())
                ->first();

            $valorMetaCalculado = $valorMeta;
            if (!$metaExistente && $valorMetaCalculado <= 0) {
                $valorMetaCalculado = $this->calcularMetaBasePorRealizado($lojaId, $tipo, $competenciaCarbon);
            }

            $dadosAtualizacao = [
                'valor_meta' => $valorMetaCalculado,
                'observacao' => $observacao,
                'status' => 'aberta',
            ];

            if ($valorRealizadoInicial !== null) {
                $dadosAtualizacao['valor_realizado_inicial'] = $valorRealizadoInicial;
            }

            $metaMensal = MetaMensal::updateOrCreate(
                [
                    'loja_id' => $lojaId,
                    'tipo' => $tipo,
                    'competencia' => $competenciaCarbon->toDateString(),
                ],
                $dadosAtualizacao
            );

            $somenteFuturos = !$metaMensal->wasRecentlyCreated;
            $this->sincronizarCompetencia($lojaId, Carbon::parse($metaMensal->competencia), $somenteFuturos);

            return $metaMensal->fresh(['diarias']);
        });
    }

    public function salvarCalendario(int $lojaId, array $diasAtivos): array
    {
        $mapaDias = ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'];

        DB::transaction(function () use ($lojaId, $diasAtivos, $mapaDias) {
            foreach ($mapaDias as $diaSemana) {
                CalendarioFuncionamento::updateOrCreate(
                    ['loja_id' => $lojaId, 'dia_semana' => $diaSemana],
                    ['ativa' => in_array($diaSemana, $diasAtivos, true)]
                );
            }

            $competencias = MetaMensal::where('loja_id', $lojaId)->pluck('competencia');
            foreach ($competencias as $competencia) {
                $this->sincronizarCompetencia($lojaId, Carbon::parse($competencia), true);
            }
        });

        return $this->listarConfiguracao($lojaId);
    }

    public function salvarExcecao(int $lojaId, string $data, string $tipo, ?string $motivo = null): ExcecaoFuncionamento
    {
        $excecao = ExcecaoFuncionamento::updateOrCreate(
            ['loja_id' => $lojaId, 'data' => Carbon::parse($data)->toDateString()],
            ['tipo' => $tipo, 'motivo' => $motivo]
        );

        $this->sincronizarCompetencia($lojaId, Carbon::parse($excecao->data)->startOfMonth(), true);

        return $excecao->fresh();
    }

    public function atualizarMetaDiaria(MetaDiaria $metaDiaria, float $valorMeta): MetaDiaria
    {
        if ($metaDiaria->travada) {
            abort(422, 'Dia fechado nao pode ser editado.');
        }

        $metaDiaria->update([
            'valor_meta' => $valorMeta,
            'eh_manual' => true,
        ]);

        $this->sincronizarCompetencia($metaDiaria->metaMensal->loja_id, Carbon::parse($metaDiaria->metaMensal->competencia), true);

        return $metaDiaria->fresh();
    }

    public function fecharCompetencia(MetaMensal $metaMensal): MetaMensal
    {
        $metaMensal->update(['status' => 'fechada']);

        return $metaMensal->fresh(['diarias']);
    }

    public function montarResumo(int $lojaId, ?string $competenciaInput = null): array
    {
        $competencia = Carbon::parse($competenciaInput ?? now()->startOfMonth())->startOfMonth();
        $inicio = $competencia->copy()->startOfMonth();
        $fim = $competencia->copy()->endOfMonth();

        $configuracao = $this->listarConfiguracao($lojaId);

        $metas = MetaMensal::with(['diarias' => function ($query) {
            $query->orderBy('data');
        }])
            ->where('loja_id', $lojaId)
            ->whereDate('competencia', $competencia->toDateString())
            ->get()
            ->keyBy('tipo');

        foreach (['venda', 'saldo'] as $tipo) {
            if (!isset($metas[$tipo])) {
                $metas[$tipo] = new MetaMensal([
                    'loja_id' => $lojaId,
                    'tipo' => $tipo,
                    'competencia' => $competencia->toDateString(),
                    'valor_meta' => 0,
                    'valor_realizado_inicial' => 0,
                    'valor_realizado' => 0,
                    'valor_restante' => 0,
                    'percentual_atingido' => 0,
                    'media_necessaria_dia' => 0,
                    'dias_funcionamento' => 0,
                    'dias_restantes' => 0,
                    'status' => 'aberta',
                ]);
                $metas[$tipo]->setRelation('diarias', collect());
            }
        }

        return [
            'competencia' => $competencia->toDateString(),
            'inicio' => $inicio->toDateString(),
            'fim' => $fim->toDateString(),
            'calendario' => $configuracao['calendario'],
            'excecoes' => $configuracao['excecoes'],
            'metas' => [
                'venda' => $this->formatarMeta($metas['venda'], 'venda', $lojaId, $competencia, $inicio, $fim),
                'saldo' => $this->formatarMeta($metas['saldo'], 'saldo', $lojaId, $competencia, $inicio, $fim),
            ],
        ];
    }

    public function resumoPeriodo(int $lojaId, string $inicio, string $fim): array
    {
        $inicioCarbon = Carbon::parse($inicio)->startOfMonth();
        $fimCarbon = Carbon::parse($fim)->endOfMonth();

        $metas = MetaMensal::where('loja_id', $lojaId)
            ->whereBetween('competencia', [$inicioCarbon->toDateString(), $fimCarbon->toDateString()])
            ->get()
            ->groupBy('tipo');

        $configuracao = $this->listarConfiguracao($lojaId);

        $result = [];
        foreach (['venda', 'saldo'] as $tipo) {
            $tipoMetas = $metas->get($tipo, collect());

            $valorMeta = (float) $tipoMetas->sum('valor_meta');
            $valorRealizado = (float) $tipoMetas->sum('valor_realizado');
            $valorRestante = max(0.0, round($valorMeta - $valorRealizado, 2));
            $percentual = $valorMeta > 0 ? round(($valorRealizado / $valorMeta) * 100, 2) : 0.0;
            $diasFuncionamento = (int) $tipoMetas->sum('dias_funcionamento');
            $diasRestantes = (int) $tipoMetas->sum('dias_restantes');
            $mediaNecessaria = $diasRestantes > 0 ? round($valorRestante / $diasRestantes, 2) : 0.0;

            $metaMensalIds = $tipoMetas->pluck('id');
            $diarias = MetaDiaria::whereIn('meta_mensal_id', $metaMensalIds)
                ->whereBetween('data', [$inicio, $fim])
                ->orderBy('data')
                ->get();

            $result[$tipo] = [
                'valor_meta' => $valorMeta,
                'valor_realizado_inicial' => (float) $tipoMetas->sum('valor_realizado_inicial'),
                'valor_realizado' => $valorRealizado,
                'valor_restante' => $valorRestante,
                'percentual_atingido' => $percentual,
                'media_necessaria_dia' => $mediaNecessaria,
                'dias_funcionamento' => $diasFuncionamento,
                'dias_restantes' => $diasRestantes,
                'dias' => $diarias->map(fn (MetaDiaria $d) => [
                    'id' => $d->id,
                    'data' => $d->data->toDateString(),
                    'valor_meta' => (float) $d->valor_meta,
                    'valor_realizado' => (float) $d->valor_realizado,
                    'saldo_diario' => (float) $d->saldo_diario,
                    'diferenca' => (float) $d->diferenca,
                    'status' => $d->status,
                    'eh_manual' => (bool) $d->eh_manual,
                    'travada' => (bool) $d->travada,
                ])->values(),
            ];
        }

        return [
            'competencia' => $inicioCarbon->toDateString(),
            'inicio' => $inicio,
            'fim' => $fim,
            'calendario' => $configuracao['calendario'],
            'excecoes' => $configuracao['excecoes'],
            'metas' => $result,
        ];
    }

    public function resumoAnual(int $lojaId, int $ano): array
    {
        $inicioAno = Carbon::createFromDate($ano, 1, 1)->startOfYear();
        $fimAno = $inicioAno->copy()->endOfYear();

        $metasAno = MetaMensal::where('loja_id', $lojaId)
            ->whereBetween('competencia', [$inicioAno->toDateString(), $fimAno->toDateString()])
            ->get()
            ->keyBy(fn (MetaMensal $meta) => $meta->competencia->format('Y-m').':'.$meta->tipo);

        $meses = [];
        for ($mes = 1; $mes <= 12; $mes++) {
            $competencia = Carbon::createFromDate($ano, $mes, 1)->startOfMonth();
            $chaveMes = $competencia->format('Y-m');

            $meses[] = [
                'mes' => $mes,
                'competencia' => $competencia->toDateString(),
                'venda' => $this->formatarResumoMensal($metasAno->get($chaveMes.':venda')),
                'saldo' => $this->formatarResumoMensal($metasAno->get($chaveMes.':saldo')),
            ];
        }

        return [
            'ano' => $ano,
            'meses' => $meses,
        ];
    }

    public function sincronizarCompetencia(int $lojaId, Carbon $competencia, bool $somenteFuturos = false): void
    {
        $inicio = $competencia->copy()->startOfMonth();
        $fim = $competencia->copy()->endOfMonth();
        $hoje = now()->startOfDay();

        $calendario = $this->obterCalendarioOuPadrao($lojaId);
        $excecoes = ExcecaoFuncionamento::where('loja_id', $lojaId)
            ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
            ->get()
            ->keyBy(fn ($item) => $item->data->toDateString());

        $diasFuncionamento = [];
        foreach (CarbonPeriod::create($inicio, $fim) as $data) {
            $chave = $data->toDateString();
            $excecao = $excecoes[$chave] ?? null;

            if ($excecao) {
                if ($excecao->tipo === 'aberto') {
                    $diasFuncionamento[] = $data->copy();
                }
                continue;
            }

            $diaSemana = $this->mapearDiaSemana($data->dayOfWeekIso);
            if (($calendario[$diaSemana]?->ativa ?? false) === true) {
                $diasFuncionamento[] = $data->copy();
            }
        }

        $qtdDiasFuncionamento = count($diasFuncionamento);

        foreach (['venda', 'saldo'] as $tipo) {
            $metaMensal = MetaMensal::firstOrCreate(
                [
                    'loja_id' => $lojaId,
                    'tipo' => $tipo,
                    'competencia' => $inicio->toDateString(),
                ],
                ['valor_meta' => 0, 'status' => 'aberta']
            );

            if ($metaMensal->status === 'fechada') {
                continue;
            }

            $diariasExistentes = MetaDiaria::where('meta_mensal_id', $metaMensal->id)
                ->whereIn('data', array_map(fn ($date) => $date->toDateString(), $diasFuncionamento))
                ->get()
                ->keyBy(fn ($item) => $item->data->toDateString());

            $valorFixado = 0.0;
            $diasRedistribuiveis = [];

            foreach ($diasFuncionamento as $data) {
                $chave = $data->toDateString();
                $metaDiariaExistente = $diariasExistentes->get($chave);
                $ehManual = (bool) ($metaDiariaExistente?->eh_manual ?? false);
                $ehDiaElegivel = !$somenteFuturos || $data->greaterThanOrEqualTo($hoje);

                if (!$ehManual && $ehDiaElegivel) {
                    $diasRedistribuiveis[] = $chave;
                    continue;
                }

                $valorFixado += (float) ($metaDiariaExistente?->valor_meta ?? 0);
            }

            $diasRedistribuiveisMap = array_fill_keys($diasRedistribuiveis, true);
            $saldoBase = max(0.0, round((float) $metaMensal->valor_meta - $valorFixado, 2));
            $valorBaseDiario = count($diasRedistribuiveis) > 0 ? round($saldoBase / count($diasRedistribuiveis), 2) : 0.0;

            foreach ($diasFuncionamento as $data) {
                $chave = $data->toDateString();
                $caixa = CaixaDiario::where('loja_id', $lojaId)
                    ->whereDate('data', $chave)
                    ->first();

                $valorRealizado = 0.0;
                $saldoDiario = 0.0;
                $travada = false;

                if ($caixa) {
                    $caixa->loadMissing('entradas');
                    $valorRealizado = $tipo === 'venda'
                        ? (float) $caixa->total_entradas
                        : (float) $caixa->saldo;
                    $saldoDiario = (float) $caixa->saldo;
                    $travada = $caixa->status === 'fechado';
                }

                $metaDiaria = MetaDiaria::firstOrNew([
                    'meta_mensal_id' => $metaMensal->id,
                    'data' => $chave,
                ]);

                $deveRedistribuirDia = isset($diasRedistribuiveisMap[$chave]);

                if ($deveRedistribuirDia && !$metaDiaria->eh_manual) {
                    $metaDiaria->valor_meta = $valorBaseDiario;
                } elseif (!$metaDiaria->exists) {
                    $metaDiaria->valor_meta = 0;
                }

                $metaDiaria->valor_realizado = $valorRealizado;
                $metaDiaria->saldo_diario = $saldoDiario;
                $metaDiaria->diferenca = round($valorRealizado - (float) $metaDiaria->valor_meta, 2);
                $metaDiaria->status = $this->classificarMeta((float) $metaDiaria->valor_meta, $valorRealizado);
                $metaDiaria->travada = $travada;

                $metaDiaria->save();
            }

            $relacaoDiarias = MetaDiaria::where('meta_mensal_id', $metaMensal->id)
                ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
                ->orderBy('data')
                ->get();

            $valorRealizadoCaixa = (float) $relacaoDiarias->sum('valor_realizado');
            $valorRealizadoTotal = round((float) $metaMensal->valor_realizado_inicial + $valorRealizadoCaixa, 2);
            $diasRestantes = $this->contarDiasRestantes($diasFuncionamento, now());
            $valorRestante = max(0.0, round((float) $metaMensal->valor_meta - $valorRealizadoTotal, 2));
            $percentual = (float) $metaMensal->valor_meta > 0
                ? round(($valorRealizadoTotal / (float) $metaMensal->valor_meta) * 100, 2)
                : 0.0;
            $mediaNecessaria = $diasRestantes > 0 ? round($valorRestante / $diasRestantes, 2) : 0.0;

            $metaMensal->update([
                'valor_realizado' => $valorRealizadoTotal,
                'valor_restante' => $valorRestante,
                'percentual_atingido' => $percentual,
                'media_necessaria_dia' => $mediaNecessaria,
                'dias_funcionamento' => $qtdDiasFuncionamento,
                'dias_restantes' => $diasRestantes,
            ]);
        }
    }

    public function listarConfiguracao(int $lojaId): array
    {
        $this->obterCalendarioOuPadrao($lojaId);

        return [
            'calendario' => CalendarioFuncionamento::where('loja_id', $lojaId)
                ->orderByRaw("array_position(array['segunda','terca','quarta','quinta','sexta','sabado','domingo'], dia_semana)")
                ->get(['dia_semana', 'ativa']),
            'excecoes' => ExcecaoFuncionamento::where('loja_id', $lojaId)
                ->orderByDesc('data')
                ->get(['data', 'tipo', 'motivo']),
        ];
    }

    private function obterCalendarioOuPadrao(int $lojaId)
    {
        $calendario = CalendarioFuncionamento::where('loja_id', $lojaId)->get()->keyBy('dia_semana');

        if ($calendario->isNotEmpty()) {
            return $calendario;
        }

        $padrao = [
            'segunda' => true,
            'terca' => true,
            'quarta' => true,
            'quinta' => true,
            'sexta' => true,
            'sabado' => true,
            'domingo' => false,
        ];

        foreach ($padrao as $diaSemana => $ativa) {
            CalendarioFuncionamento::updateOrCreate(
                ['loja_id' => $lojaId, 'dia_semana' => $diaSemana],
                ['ativa' => $ativa]
            );
        }

        return CalendarioFuncionamento::where('loja_id', $lojaId)->get()->keyBy('dia_semana');
    }

    private function formatarMeta(MetaMensal $metaMensal, string $tipo, int $lojaId, Carbon $competencia, Carbon $inicio, Carbon $fim): array
    {
        $diarias = MetaDiaria::where('meta_mensal_id', $metaMensal->id)
            ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
            ->orderBy('data')
            ->get();

        return [
            'id' => $metaMensal->id,
            'tipo' => $tipo,
            'competencia' => $competencia->toDateString(),
            'status' => $metaMensal->status,
            'valor_meta' => (float) $metaMensal->valor_meta,
            'valor_meta_sugerido' => (float) ($metaMensal->valor_meta > 0
                ? $metaMensal->valor_meta
                : $this->calcularMetaBasePorRealizado($lojaId, $tipo, $competencia)),
            'valor_realizado_inicial' => (float) $metaMensal->valor_realizado_inicial,
            'valor_realizado' => (float) $metaMensal->valor_realizado,
            'valor_restante' => (float) $metaMensal->valor_restante,
            'percentual_atingido' => (float) $metaMensal->percentual_atingido,
            'media_necessaria_dia' => (float) $metaMensal->media_necessaria_dia,
            'dias_funcionamento' => (int) $metaMensal->dias_funcionamento,
            'dias_restantes' => (int) $metaMensal->dias_restantes,
            'dias' => $diarias->map(function (MetaDiaria $metaDiaria) {
                return [
                    'id' => $metaDiaria->id,
                    'data' => $metaDiaria->data->toDateString(),
                    'valor_meta' => (float) $metaDiaria->valor_meta,
                    'valor_realizado' => (float) $metaDiaria->valor_realizado,
                    'saldo_diario' => (float) $metaDiaria->saldo_diario,
                    'diferenca' => (float) $metaDiaria->diferenca,
                    'status' => $metaDiaria->status,
                    'eh_manual' => (bool) $metaDiaria->eh_manual,
                    'travada' => (bool) $metaDiaria->travada,
                ];
            })->values(),
        ];
    }

    private function formatarResumoMensal(?MetaMensal $metaMensal): array
    {
        return [
            'id' => $metaMensal?->id,
            'status' => $metaMensal?->status ?? 'aberta',
            'valor_meta' => (float) ($metaMensal?->valor_meta ?? 0),
            'valor_meta_sugerido' => (float) (($metaMensal && $metaMensal->valor_meta > 0)
                ? $metaMensal->valor_meta
                : ($metaMensal
                    ? $this->calcularMetaBasePorRealizado($metaMensal->loja_id, $metaMensal->tipo, Carbon::parse($metaMensal->competencia))
                    : 0)),
            'valor_realizado_inicial' => (float) ($metaMensal?->valor_realizado_inicial ?? 0),
            'valor_realizado' => (float) ($metaMensal?->valor_realizado ?? 0),
            'valor_restante' => (float) ($metaMensal?->valor_restante ?? 0),
            'percentual_atingido' => (float) ($metaMensal?->percentual_atingido ?? 0),
            'media_necessaria_dia' => (float) ($metaMensal?->media_necessaria_dia ?? 0),
            'dias_funcionamento' => (int) ($metaMensal?->dias_funcionamento ?? 0),
            'dias_restantes' => (int) ($metaMensal?->dias_restantes ?? 0),
        ];
    }

    private function classificarMeta(float $meta, float $realizado): string
    {
        if ($realizado > $meta) {
            return 'acima';
        }

        if (abs($realizado - $meta) < 0.01) {
            return 'dentro';
        }

        return 'abaixo';
    }

    private function contarDiasRestantes(array $diasFuncionamento, Carbon $hoje): int
    {
        return collect($diasFuncionamento)
            ->filter(fn (Carbon $data) => $data->greaterThanOrEqualTo($hoje->copy()->startOfDay()))
            ->count();
    }

    private function mapearDiaSemana(int $dayOfWeekIso): string
    {
        return match ($dayOfWeekIso) {
            1 => 'segunda',
            2 => 'terca',
            3 => 'quarta',
            4 => 'quinta',
            5 => 'sexta',
            6 => 'sabado',
            7 => 'domingo',
        };
    }

    private function calcularMetaBasePorRealizado(int $lojaId, string $tipo, Carbon $competencia): float
    {
        $inicio = $competencia->copy()->startOfMonth();
        $fim = $competencia->copy()->endOfMonth();

        $realizado = $tipo === 'venda'
            ? (float) CaixaDiario::where('loja_id', $lojaId)
                ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
                ->sum('total_entradas')
            : (float) CaixaDiario::where('loja_id', $lojaId)
                ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
                ->sum('saldo');

        if ($realizado > 0) {
            return round($realizado, 2);
        }

        return $tipo === 'venda' ? 30000.00 : 18000.00;
    }
}