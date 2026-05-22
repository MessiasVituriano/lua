<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BanhoTosaCusto extends Model
{
    protected $table = 'banho_tosa_custos';

    protected $fillable = [
        'loja_id',
        'servico_id',
        'descricao',
        'tipo',
        'valor',
        'data_custo',
        'origem',
        'observacao',
    ];

    protected $casts = [
        'valor'      => 'decimal:2',
        'data_custo' => 'date',
    ];

    public function loja(): BelongsTo
    {
        return $this->belongsTo(Loja::class);
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(BanhoTosaServico::class, 'servico_id');
    }
}
