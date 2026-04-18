<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimentacoes_internas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas');
            $table->foreignId('loja_destino_id')->nullable()->constrained('lojas');
            $table->enum('tipo', ['transferencia_banco', 'sangria', 'aporte', 'transferencia_loja']);
            $table->foreignId('banco_origem_id')->nullable()->constrained('bancos');
            $table->foreignId('banco_destino_id')->nullable()->constrained('bancos');
            $table->decimal('valor', 10, 2);
            $table->string('descricao');
            $table->date('data_movimentacao');
            $table->enum('status', ['solicitada', 'aprovada', 'rejeitada'])->default('solicitada');
            $table->foreignId('solicitado_por')->constrained('users');
            $table->foreignId('aprovado_por')->nullable()->constrained('users');
            $table->timestamp('aprovado_em')->nullable();
            $table->string('motivo_rejeicao')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_internas');
    }
};
