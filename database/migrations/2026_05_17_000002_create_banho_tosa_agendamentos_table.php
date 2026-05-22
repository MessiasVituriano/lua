<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banho_tosa_agendamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->foreignId('pet_id')->nullable()->constrained('pets')->onDelete('set null');
            $table->foreignId('servico_id')->nullable()->constrained('banho_tosa_servicos')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // atendente responsável
            $table->date('data');
            $table->time('horario_inicio');
            $table->time('horario_fim');
            $table->decimal('valor_estimado', 10, 2)->nullable();
            $table->decimal('valor_final', 10, 2)->nullable();
            $table->string('status', 30)->default('solicitado'); // solicitado, confirmado, em_andamento, concluido, cancelado, faltou
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index(['loja_id', 'data']);
            $table->index(['loja_id', 'status']);
            $table->index(['pet_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banho_tosa_agendamentos');
    }
};
