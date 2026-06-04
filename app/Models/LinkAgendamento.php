<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkAgendamento extends Model
{
    protected $table = 'link_agendamentos';

    protected $fillable = [
        'loja_id',
        'cliente_id',
        'criado_por',
        'token',
        'expires_at',
        'usado_em',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'usado_em'   => 'datetime',
    ];

    public function loja(): BelongsTo
    {
        return $this->belongsTo(Loja::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function estaValido(): bool
    {
        return $this->expires_at->isFuture() && is_null($this->usado_em);
    }
}
