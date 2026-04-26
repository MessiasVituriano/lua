<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'loja_id',
        'fornecedor_id',
        'nome',
        'categoria',
        'valor_custo',
        'margem',
        'valor_venda',
        'estoque_atual',
        'estoque_min',
        'ativo',
    ];

    protected $casts = [
        'valor_custo' => 'decimal:2',
        'margem' => 'decimal:2',
        'valor_venda' => 'decimal:2',
        'ativo' => 'boolean',
    ];

    public const CATEGORIAS = [
        'racao' => 'Ração',
        'medicamento' => 'Medicamento',
        'acessorio' => 'Acessório',
        'higiene' => 'Higiene',
        'petisco' => 'Petisco',
    ];

    public static function calcularValorVenda(float $custo, float $margem): float
    {
        return round($custo * (1 + $margem / 100), 2);
    }

    public function loja()
    {
        return $this->belongsTo(Loja::class);
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function movimentacoes()
    {
        return $this->hasMany(MovimentacaoEstoque::class);
    }
}
