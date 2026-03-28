<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caixas_diarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas');
            $table->date('data');
            $table->enum('status', ['aberto', 'fechado'])->default('aberto');
            $table->decimal('total_entradas', 10, 2)->default(0);
            $table->decimal('total_saidas', 10, 2)->default(0);
            $table->decimal('saldo', 10, 2)->default(0);
            $table->foreignId('fechado_por')->nullable()->constrained('users');
            $table->timestamp('fechado_em')->nullable();
            $table->timestamps();
            $table->unique(['loja_id', 'data']);
        });

        Schema::create('entradas_caixa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caixa_diario_id')->constrained('caixas_diarios')->onDelete('cascade');
            $table->enum('forma_recebimento', ['dinheiro', 'pix', 'cartao_debito', 'cartao_credito']);
            $table->foreignId('banco_id')->nullable()->constrained('bancos');
            $table->decimal('valor', 10, 2);
            $table->string('descricao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entradas_caixa');
        Schema::dropIfExists('caixas_diarios');
    }
};
