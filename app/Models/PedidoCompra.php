<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoCompra extends Model
{
    use HasFactory;

    protected $table = 'pedidos_compra';

    protected $fillable = [
        'loja_id',
        'fornecedor_id',
        'status',
        'data_estimativa_entrega',
        'data_entrega',
        'valor_total',
        'observacao',
        'data_vencimento',
        'forma_pagamento',
        'banco_id',
        'quantidade_parcelas',
        'recorrencia_dias',
        'usuario_id',
        'confirmado_por',
        'confirmado_em',
        'entregue_por',
        'entregue_em',
        'cancelado_por',
        'cancelado_em',
    ];

    protected $casts = [
        'data_estimativa_entrega' => 'date:Y-m-d',
        'data_entrega' => 'date:Y-m-d',
        'data_vencimento' => 'date:Y-m-d',
        'confirmado_em' => 'datetime',
        'entregue_em' => 'datetime',
        'cancelado_em' => 'datetime',
        'valor_total' => 'decimal:2',
    ];

    public const STATUS = [
        'pendente' => 'Pendente',
        'confirmado' => 'Confirmado',
        'entregue' => 'Entregue',
        'cancelado' => 'Cancelado',
    ];

    public function loja()
    {
        return $this->belongsTo(Loja::class);
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function banco()
    {
        return $this->belongsTo(Banco::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function confirnadoPorUsuario()
    {
        return $this->belongsTo(User::class, 'confirmado_por');
    }

    public function entregueporUsuario()
    {
        return $this->belongsTo(User::class, 'entregue_por');
    }

    public function itens()
    {
        return $this->hasMany(PedidoCompraItem::class);
    }

    public function pagamentos()
    {
        return $this->hasMany(Pagamento::class);
    }
}
