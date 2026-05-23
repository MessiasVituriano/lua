<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas');
            $table->foreignId('fornecedor_id')->constrained('fornecedores');
            $table->enum('status', ['pendente', 'confirmado', 'entregue', 'cancelado'])->default('pendente');
            $table->date('data_estimativa_entrega');
            $table->date('data_entrega')->nullable();
            $table->decimal('valor_total', 10, 2)->default(0);
            $table->text('observacao')->nullable();

            // Dados para geracao dos pagamentos ao confirmar
            $table->date('data_vencimento')->nullable();
            $table->enum('forma_pagamento', ['dinheiro', 'pix', 'boleto', 'transferencia'])->nullable();
            $table->foreignId('banco_id')->nullable()->constrained('bancos');
            $table->integer('quantidade_parcelas')->default(1);
            $table->integer('recorrencia_dias')->nullable();

            // Rastreabilidade
            $table->foreignId('usuario_id')->constrained('users');
            $table->foreignId('confirmado_por')->nullable()->constrained('users');
            $table->dateTime('confirmado_em')->nullable();
            $table->foreignId('entregue_por')->nullable()->constrained('users');
            $table->dateTime('entregue_em')->nullable();
            $table->foreignId('cancelado_por')->nullable()->constrained('users');
            $table->dateTime('cancelado_em')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos_compra');
    }
};
