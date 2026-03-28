<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fornecedor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fornecedores';

    protected $fillable = [
        'nome',
        'categoria',
        'telefone',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public const CATEGORIAS = [
        'racao' => 'Ração',
        'medicamento' => 'Medicamento',
        'acessorio' => 'Acessório',
        'higiene' => 'Higiene',
        'outros' => 'Outros',
    ];
}
