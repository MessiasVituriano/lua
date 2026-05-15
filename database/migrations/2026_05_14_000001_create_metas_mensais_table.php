<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metas_mensais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->cascadeOnDelete();
            $table->enum('tipo', ['venda', 'saldo']);
            $table->date('competencia');
            $table->decimal('valor_meta', 10, 2)->default(0);
            $table->decimal('valor_realizado', 10, 2)->default(0);
            $table->decimal('valor_restante', 10, 2)->default(0);
            $table->decimal('percentual_atingido', 8, 2)->default(0);
            $table->decimal('media_necessaria_dia', 10, 2)->default(0);
            $table->unsignedInteger('dias_funcionamento')->default(0);
            $table->unsignedInteger('dias_restantes')->default(0);
            $table->enum('status', ['aberta', 'fechada'])->default('aberta');
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->unique(['loja_id', 'tipo', 'competencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metas_mensais');
    }
};