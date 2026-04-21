<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanoMaquininhaTaxa extends Model
{
    use HasFactory;

    protected $table = 'plano_maquininha_taxas';

    protected $fillable = [
        'plano_maquininha_id',
        'bandeira_id',
        'modalidade',
        'taxa',
    ];

    protected $casts = [
        'taxa' => 'decimal:2',
    ];

    public function plano()
    {
        return $this->belongsTo(PlanoMaquininha::class, 'plano_maquininha_id');
    }

    public function bandeira()
    {
        return $this->belongsTo(Bandeira::class);
    }
}
