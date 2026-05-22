<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banho_tosa_servicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->nullable()->constrained('lojas')->onDelete('cascade');
            $table->string('nome', 120);
            $table->string('categoria', 30); // banho, tosa, pacote, extra
            $table->decimal('preco_base', 10, 2);
            $table->decimal('custo_estimado', 10, 2)->nullable();
            $table->unsignedSmallInteger('duracao_minutos')->default(60);
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['loja_id', 'ativo']);
            $table->index('categoria');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banho_tosa_servicos');
    }
};
