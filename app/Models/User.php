<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'loja_id',
        'ativo',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'ativo' => 'boolean',
    ];

    public function lojaAtiva()
    {
        return $this->belongsTo(Loja::class, 'loja_id');
    }

    public function lojas()
    {
        return $this->belongsToMany(Loja::class, 'usuario_loja');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAtendente(): bool
    {
        return $this->role === 'atendente';
    }
}
