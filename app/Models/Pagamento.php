<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pagamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'loja_id',
        'fornecedor_id',
        'categoria',
        'descricao',
        'valor_total',
        'valor_pago',
        'data_vencimento',
        'data_pagamento',
        'forma_pagamento',
        'banco_id',
        'status',
        'observacao',
        'recorrente',
        'dia_recorrencia',
    ];

    protected $casts = [
        'valor_total' => 'decimal:2',
        'valor_pago' => 'decimal:2',
        'data_vencimento' => 'date',
        'data_pagamento' => 'date',
        'recorrente' => 'boolean',
    ];

    public const CATEGORIAS = [
        'boleto' => 'Boleto',
        'imposto' => 'Imposto',
        'custo_fixo' => 'Custo Fixo',
        'funcionario' => 'Funcionário',
        'fornecedor' => 'Fornecedor',
        'outros' => 'Outros',
    ];

    public const FORMAS_PAGAMENTO = [
        'dinheiro' => 'Dinheiro',
        'pix' => 'PIX',
        'boleto' => 'Boleto',
        'transferencia' => 'Transferência',
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
}
