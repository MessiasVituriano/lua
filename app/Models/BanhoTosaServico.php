<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BanhoTosaServico extends Model
{
    protected $table = 'banho_tosa_servicos';

    protected $fillable = [
        'loja_id',
        'nome',
        'categoria',
        'preco_base',
        'custo_estimado',
        'duracao_minutos',
        'descricao',
        'ativo',
    ];

    protected $casts = [
        'preco_base'       => 'decimal:2',
        'custo_estimado'   => 'decimal:2',
        'duracao_minutos'  => 'integer',
        'ativo'            => 'boolean',
    ];

    public function loja(): BelongsTo
    {
        return $this->belongsTo(Loja::class);
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(BanhoTosaAgendamento::class, 'servico_id');
    }

    public function custos(): HasMany
    {
        return $this->hasMany(BanhoTosaCusto::class, 'servico_id');
    }
}
