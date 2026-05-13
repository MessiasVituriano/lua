<?php

use App\Models\EntradaCaixa;
use App\Models\User;

$usuarioEmail = 'trocar@exemplo.com';
$somenteSemUsuario = true;

$user = User::query()->where('email', $usuarioEmail)->first();

if (!$user) {
    echo "Usuario nao encontrado para o e-mail: {$usuarioEmail}" . PHP_EOL;
    return;
}

$query = EntradaCaixa::query()->whereHas('itens');

if ($somenteSemUsuario) {
    $query->whereNull('user_id');
}

$totalAlvo = (clone $query)->count();

$atualizados = $query->update([
    'user_id' => $user->id,
]);

echo "Usuario alvo: {$user->name} (#{$user->id})" . PHP_EOL;
echo "Vendas alvo: {$totalAlvo}" . PHP_EOL;
echo "Vendas atualizadas: {$atualizados}" . PHP_EOL;
