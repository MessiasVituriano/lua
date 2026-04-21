<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bandeira extends Model
{
    use HasFactory;

    protected $table = 'bandeiras';

    protected $fillable = [
        'loja_id',
        'nome',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function loja()
    {
        return $this->belongsTo(Loja::class);
    }
}
