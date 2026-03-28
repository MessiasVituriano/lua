<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas');
            $table->foreignId('fornecedor_id')->nullable()->constrained('fornecedores');
            $table->enum('categoria', ['boleto', 'imposto', 'custo_fixo', 'funcionario', 'fornecedor', 'outros']);
            $table->string('descricao');
            $table->decimal('valor_total', 10, 2);
            $table->decimal('valor_pago', 10, 2)->default(0);
            $table->date('data_vencimento');
            $table->date('data_pagamento')->nullable();
            $table->enum('forma_pagamento', ['dinheiro', 'pix', 'boleto', 'transferencia'])->nullable();
            $table->foreignId('banco_id')->nullable()->constrained('bancos');
            $table->enum('status', ['pendente', 'pago', 'atrasado', 'parcial'])->default('pendente');
            $table->text('observacao')->nullable();
            $table->boolean('recorrente')->default(false);
            $table->integer('dia_recorrencia')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamentos');
    }
};
