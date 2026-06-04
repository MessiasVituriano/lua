<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarioFuncionamento extends Model
{
    use HasFactory;

    protected $table = 'calendario_funcionamento';

    protected $fillable = [
        'loja_id',
        'dia_semana',
        'ativa',
        'horario_abertura',
        'horario_fechamento',
    ];

    protected $casts = [
        'ativa' => 'boolean',
    ];

    public function loja()
    {
        return $this->belongsTo(Loja::class);
    }
}