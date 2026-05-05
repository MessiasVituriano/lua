<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'loja_id',
        'nome',
        'telefone',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function loja()
    {
        return $this->belongsTo(Loja::class);
    }

    public function pets()
    {
        return $this->hasMany(Pet::class);
    }
}
