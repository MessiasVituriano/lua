<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas');
            $table->foreignId('fornecedor_id')->nullable()->constrained('fornecedores');
            $table->string('nome');
            $table->enum('categoria', ['racao', 'medicamento', 'acessorio', 'higiene']);
            $table->decimal('valor_custo', 10, 2);
            $table->decimal('margem', 5, 2);
            $table->decimal('valor_venda', 10, 2);
            $table->integer('estoque_atual')->default(0);
            $table->integer('estoque_min')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('movimentacoes_estoque', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->onDelete('cascade');
            $table->enum('tipo', ['entrada', 'saida']);
            $table->integer('quantidade');
            $table->string('motivo')->nullable();
            $table->foreignId('usuario_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_estoque');
        Schema::dropIfExists('produtos');
    }
};
