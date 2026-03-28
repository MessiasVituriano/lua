<?php

namespace Database\Seeders;

use App\Models\Banco;
use App\Models\Fornecedor;
use App\Models\Loja;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Loja padrao
        $loja = Loja::create([
            'nome' => 'LUA PetShop - Matriz',
            'endereco' => 'Rua Principal, 100',
            'telefone' => '(11) 99999-0000',
        ]);

        // Usuario admin
        $user = User::create([
            'name' => 'Administrador',
            'email' => 'admin@lua.com',
            'password' => 'password',
            'loja_id' => $loja->id,
            'ativo' => true,
        ]);

        $user->lojas()->attach($loja->id);

        // Bancos
        $bancos = ['Nubank', 'Itau', 'Bradesco', 'Banco do Brasil', 'Caixa Economica'];
        foreach ($bancos as $nome) {
            Banco::create(['nome' => $nome]);
        }

        // Fornecedores
        $fornecedores = [
            ['nome' => 'PremieR Pet', 'categoria' => 'racao', 'telefone' => '(11) 91111-1111'],
            ['nome' => 'Royal Canin', 'categoria' => 'racao', 'telefone' => '(11) 92222-2222'],
            ['nome' => 'Ouro Fino', 'categoria' => 'medicamento', 'telefone' => '(11) 93333-3333'],
            ['nome' => 'Chalesco', 'categoria' => 'acessorio', 'telefone' => '(11) 94444-4444'],
            ['nome' => 'Sanol', 'categoria' => 'higiene', 'telefone' => '(11) 95555-5555'],
        ];

        foreach ($fornecedores as $f) {
            Fornecedor::create($f);
        }
    }
}
