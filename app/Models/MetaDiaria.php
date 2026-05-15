<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaDiaria extends Model
{
    use HasFactory;

    protected $table = 'metas_diarias';

    protected $fillable = [
        'meta_mensal_id',
        'data',
        'valor_meta',
        'valor_realizado',
        'saldo_diario',
        'diferenca',
        'status',
        'eh_manual',
        'travada',
    ];

    protected $casts = [
        'data' => 'date',
        'valor_meta' => 'decimal:2',
        'valor_realizado' => 'decimal:2',
        'saldo_diario' => 'decimal:2',
        'diferenca' => 'decimal:2',
        'eh_manual' => 'boolean',
        'travada' => 'boolean',
    ];

    public function metaMensal()
    {
        return $this->belongsTo(MetaMensal::class, 'meta_mensal_id');
    }
}