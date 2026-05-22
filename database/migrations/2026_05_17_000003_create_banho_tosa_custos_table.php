<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banho_tosa_custos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->foreignId('servico_id')->nullable()->constrained('banho_tosa_servicos')->onDelete('set null');
            $table->string('descricao', 255);
            $table->string('tipo', 30); // fixo, variavel, recorrente, insumo, comissao
            $table->decimal('valor', 10, 2);
            $table->date('data_custo');
            $table->string('origem', 30)->default('manual'); // manual, pagamento, atendimento
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index(['loja_id', 'data_custo']);
            $table->index(['loja_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banho_tosa_custos');
    }
};
