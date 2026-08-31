<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaixaDiario extends Model
{
    use HasFactory;

    protected $table = 'caixas_diarios';

    protected $fillable = [
        'loja_id',
        'data',
        'status',
        'total_entradas',
        'total_saidas',
        'saldo',
        'fechado_por',
        'fechado_em',
    ];

    protected $casts = [
        'data' => 'date',
        'total_entradas' => 'decimal:2',
        'total_saidas' => 'decimal:2',
        'saldo' => 'decimal:2',
        'fechado_em' => 'datetime',
        'autorizado_em' => 'datetime',
    ];

    public function loja()
    {
        return $this->belongsTo(Loja::class);
    }

    public function entradas()
    {
        return $this->hasMany(EntradaCaixa::class);
    }

    public function fechadoPor()
    {
        return $this->belongsTo(User::class, 'fechado_por');
    }

    public function autorizadoPor()
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    /**
     * Calcula os totais do dia a partir dos lancamentos, sem gravar nada.
     * Fonte unica da formula: recalcular() grava o resultado e o comando
     * caixa:recalcular usa em modo --dry-run para comparar sem alterar.
     */
    public function calcularTotais(): array
    {
        $entradas = (float) $this->entradas()->sum('valor');

        $saidas = (float) Pagamento::where('loja_id', $this->loja_id)
            ->where('data_pagamento', $this->data)
            ->whereIn('status', ['pago', 'parcial'])
            ->sum('valor_pago');

        // Movimentacoes internas (sangria, aporte, transferencias) NAO entram
        // aqui de proposito: elas movem dinheiro entre contas e patrimonio, nao
        // sao resultado do dia. Retirada do dono se lanca como pagamento de
        // categoria pro_labore, e o saldo disponivel por conta fica na tela de
        // Movimentacoes, que soma as movimentacoes por banco.
        return [
            'total_entradas' => $entradas,
            'total_saidas' => $saidas,
            'saldo' => $entradas - $saidas,
        ];
    }

    public function recalcular()
    {
        $totais = $this->calcularTotais();

        $this->total_entradas = $totais['total_entradas'];
        $this->total_saidas = $totais['total_saidas'];
        $this->saldo = $totais['saldo'];
        $this->save();

        // Atualiza o realizado nas metas diarias deste dia
        app(\App\Services\MetaService::class)->atualizarRealizadoDia(
            $this->loja_id,
            $this->data->toDateString()
        );
    }

    /**
     * Recalcula os caixas dos dias informados, ignorando dias que ainda nao
     * tem caixa aberto. Recebe pares [loja_id, data] e remove repeticoes para
     * nao sincronizar a mesma competencia varias vezes na mesma requisicao.
     */
    public static function recalcularDias(array $alvos): int
    {
        $pendentes = [];

        foreach ($alvos as [$lojaId, $data]) {
            if (!$lojaId || !$data) {
                continue;
            }

            $dia = $data instanceof \DateTimeInterface
                ? $data->format('Y-m-d')
                : (string) $data;

            $pendentes[$lojaId.':'.$dia] = [$lojaId, $dia];
        }

        $recalculados = 0;

        foreach ($pendentes as [$lojaId, $dia]) {
            $caixa = static::where('loja_id', $lojaId)->whereDate('data', $dia)->first();

            if ($caixa) {
                $caixa->recalcular();
                $recalculados++;
            }
        }

        return $recalculados;
    }
}
