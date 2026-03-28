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

    public function recalcular()
    {
        $this->total_entradas = $this->entradas()->sum('valor');

        $this->total_saidas = Pagamento::where('loja_id', $this->loja_id)
            ->where('data_pagamento', $this->data)
            ->whereIn('status', ['pago', 'parcial'])
            ->sum('valor_pago');

        $this->saldo = $this->total_entradas - $this->total_saidas;
        $this->save();
    }
}
