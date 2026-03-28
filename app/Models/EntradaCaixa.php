<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntradaCaixa extends Model
{
    use HasFactory;

    protected $table = 'entradas_caixa';

    protected $fillable = [
        'caixa_diario_id',
        'forma_recebimento',
        'banco_id',
        'valor',
        'descricao',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    public const FORMAS = [
        'dinheiro' => 'Dinheiro',
        'pix' => 'PIX',
        'cartao_debito' => 'Cartão Débito',
        'cartao_credito' => 'Cartão Crédito',
    ];

    public function caixaDiario()
    {
        return $this->belongsTo(CaixaDiario::class);
    }

    public function banco()
    {
        return $this->belongsTo(Banco::class);
    }
}
