<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excecoes_funcionamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->cascadeOnDelete();
            $table->date('data');
            $table->enum('tipo', ['aberto', 'fechado']);
            $table->string('motivo')->nullable();
            $table->timestamps();

            $table->unique(['loja_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excecoes_funcionamento');
    }
};