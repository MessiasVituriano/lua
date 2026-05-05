<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->string('nome');
            $table->string('telefone', 30)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['loja_id', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
