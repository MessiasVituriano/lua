<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metas_diarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meta_mensal_id')->constrained('metas_mensais')->cascadeOnDelete();
            $table->date('data');
            $table->decimal('valor_meta', 10, 2)->default(0);
            $table->decimal('valor_realizado', 10, 2)->default(0);
            $table->decimal('saldo_diario', 10, 2)->default(0);
            $table->decimal('diferenca', 10, 2)->default(0);
            $table->enum('status', ['acima', 'dentro', 'abaixo'])->default('abaixo');
            $table->boolean('eh_manual')->default(false);
            $table->boolean('travada')->default(false);
            $table->timestamps();

            $table->unique(['meta_mensal_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metas_diarias');
    }
};