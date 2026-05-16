<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaMensal extends Model
{
    use HasFactory;

    protected $table = 'metas_mensais';

    protected $fillable = [
        'loja_id',
        'tipo',
        'competencia',
        'valor_meta',
        'valor_realizado_inicial',
        'valor_realizado',
        'valor_restante',
        'percentual_atingido',
        'media_necessaria_dia',
        'dias_funcionamento',
        'dias_restantes',
        'status',
        'observacao',
    ];

    protected $casts = [
        'competencia' => 'date',
        'valor_meta' => 'decimal:2',
        'valor_realizado_inicial' => 'decimal:2',
        'valor_realizado' => 'decimal:2',
        'valor_restante' => 'decimal:2',
        'percentual_atingido' => 'decimal:2',
        'media_necessaria_dia' => 'decimal:2',
        'dias_funcionamento' => 'integer',
        'dias_restantes' => 'integer',
    ];

    public function loja()
    {
        return $this->belongsTo(Loja::class);
    }

    public function diarias()
    {
        return $this->hasMany(MetaDiaria::class, 'meta_mensal_id');
    }
}