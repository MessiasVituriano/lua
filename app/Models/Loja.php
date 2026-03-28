<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loja extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nome',
        'endereco',
        'telefone',
        'ativa',
    ];

    protected $casts = [
        'ativa' => 'boolean',
    ];

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'usuario_loja');
    }
}
