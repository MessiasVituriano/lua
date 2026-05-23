<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoCompraItem extends Model
{
    use HasFactory;

    protected $table = 'pedido_compra_itens';

    protected $fillable = [
        'pedido_compra_id',
        'produto_id',
        'quantidade',
        'valor_unitario',
        'valor_total',
    ];

    protected $casts = [
        'valor_unitario' => 'decimal:2',
        'valor_total' => 'decimal:2',
    ];

    public function pedidoCompra()
    {
        return $this->belongsTo(PedidoCompra::class);
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class);
    }
}
