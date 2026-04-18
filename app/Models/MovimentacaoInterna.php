<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimentacaoInterna extends Model
{
    use HasFactory;

    protected $table = 'movimentacoes_internas';

    protected $fillable = [
        'loja_id',
        'loja_destino_id',
        'tipo',
        'banco_origem_id',
        'banco_destino_id',
        'valor',
        'descricao',
        'data_movimentacao',
        'status',
        'solicitado_por',
        'aprovado_por',
        'aprovado_em',
        'motivo_rejeicao',
        'observacao',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_movimentacao' => 'date',
        'aprovado_em' => 'datetime',
    ];

    public const TIPOS = [
        'transferencia_banco' => 'Transferencia entre Bancos',
        'sangria' => 'Sangria',
        'aporte' => 'Aporte',
        'transferencia_loja' => 'Transferencia entre Lojas',
    ];

    public function loja()
    {
        return $this->belongsTo(Loja::class);
    }

    public function lojaDestino()
    {
        return $this->belongsTo(Loja::class, 'loja_destino_id');
    }

    public function bancoOrigem()
    {
        return $this->belongsTo(Banco::class, 'banco_origem_id');
    }

    public function bancoDestino()
    {
        return $this->belongsTo(Banco::class, 'banco_destino_id');
    }

    public function solicitadoPor()
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function aprovadoPor()
    {
        return $this->belongsTo(User::class, 'aprovado_por');
    }
}
