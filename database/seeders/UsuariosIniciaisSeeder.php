<?php

namespace Database\Seeders;

use App\Models\Loja;
use App\Models\User;
use Illuminate\Database\Seeder;

class UsuariosIniciaisSeeder extends Seeder
{
    public function run(): void
    {
        $loja = Loja::first();

        $usuarios = [
            ['name' => 'Messias', 'email' => 'messias@lua.com.br', 'role' => 'admin'],
            ['name' => 'Nara',    'email' => 'nara@lua.com.br',    'role' => 'admin'],
            ['name' => 'Ariel',   'email' => 'ariel@lua.com.br',   'role' => 'atendente'],
        ];

        foreach ($usuarios as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => $data['name'],
                    'password' => 'password',
                    'role'     => $data['role'],
                    'loja_id'  => $loja->id,
                    'ativo'    => true,
                ]
            );

            $user->lojas()->syncWithoutDetaching([$loja->id]);

            $this->command->info("Criado/atualizado: {$user->email} ({$user->role})");
        }
    }
}
