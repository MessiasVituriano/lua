<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'nome',
        'tipo',
        'porte',
        'raca',
        'idade_meses',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'idade_meses' => 'integer',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
