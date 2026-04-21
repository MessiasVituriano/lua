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
        'plano_maquininha_id',
        'bandeira_id',
        'parcelas',
        'taxa_aplicada',
        'valor_bruto',
        'com_antecipacao',
        'valor',
        'descricao',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'valor_bruto' => 'decimal:2',
        'taxa_aplicada' => 'decimal:2',
        'com_antecipacao' => 'boolean',
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

    public function planoMaquininha()
    {
        return $this->belongsTo(PlanoMaquininha::class);
    }

    public function bandeira()
    {
        return $this->belongsTo(Bandeira::class);
    }
}
