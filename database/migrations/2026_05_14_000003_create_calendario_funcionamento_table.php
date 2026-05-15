<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendario_funcionamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->cascadeOnDelete();
            $table->enum('dia_semana', ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo']);
            $table->boolean('ativa')->default(true);
            $table->timestamps();

            $table->unique(['loja_id', 'dia_semana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendario_funcionamento');
    }
};