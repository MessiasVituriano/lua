<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExcecaoFuncionamento extends Model
{
    use HasFactory;

    protected $table = 'excecoes_funcionamento';

    protected $fillable = [
        'loja_id',
        'data',
        'tipo',
        'motivo',
    ];

    protected $casts = [
        'data' => 'date',
    ];

    public function loja()
    {
        return $this->belongsTo(Loja::class);
    }
}