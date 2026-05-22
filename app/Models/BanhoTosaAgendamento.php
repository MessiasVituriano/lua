<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BanhoTosaAgendamento extends Model
{
    protected $table = 'banho_tosa_agendamentos';

    protected $fillable = [
        'loja_id',
        'cliente_id',
        'pet_id',
        'servico_id',
        'user_id',
        'data',
        'horario_inicio',
        'horario_fim',
        'valor_estimado',
        'valor_final',
        'status',
        'observacao',
    ];

    protected $casts = [
        'data'            => 'date',
        'valor_estimado'  => 'decimal:2',
        'valor_final'     => 'decimal:2',
    ];

    public function loja(): BelongsTo
    {
        return $this->belongsTo(Loja::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(BanhoTosaServico::class, 'servico_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
