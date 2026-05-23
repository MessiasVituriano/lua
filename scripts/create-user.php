<?php

$loja = App\Models\Loja::first();
if (!$loja) {
    $loja = App\Models\Loja::create(['nome' => 'Loja Principal', 'ativa' => true]);
    echo "Loja criada: {$loja->nome}\n";
} else {
    echo "Loja existente: {$loja->nome}\n";
}

$user = App\Models\User::updateOrCreate(
    ['email' => 'messias@lua.com.br'],
    [
        'name' => 'Messias',
        'password' => bcrypt('password'),
        'loja_id' => $loja->id,
        'role' => 'admin',
    ]
);

$user->lojas()->syncWithoutDetaching([$loja->id]);

echo "Usuário pronto: {$user->email} (role: {$user->role})\n";
