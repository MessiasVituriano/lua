<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entrada_caixa_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrada_caixa_id')->constrained('entradas_caixa')->onDelete('cascade');
            $table->foreignId('produto_id')->nullable()->constrained('produtos')->nullOnDelete();
            $table->decimal('quantidade', 10, 3);
            $table->decimal('preco_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->integer('peso_gramas')->nullable();
            $table->string('perfil_pet_tipo', 20)->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->date('data_proxima_compra_estimada')->nullable();
            $table->timestamps();

            $table->index('entrada_caixa_id');
            $table->index('produto_id');
            $table->index('pet_id');
            $table->index('perfil_pet_tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entrada_caixa_itens');
    }
};
