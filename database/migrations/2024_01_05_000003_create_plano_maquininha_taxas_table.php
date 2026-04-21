<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plano_maquininha_taxas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plano_maquininha_id')->constrained('planos_maquininha')->onDelete('cascade');
            $table->foreignId('bandeira_id')->constrained('bandeiras')->onDelete('cascade');
            $table->enum('modalidade', ['debito', 'credito_avista', 'credito_2_6', 'credito_7_12']);
            $table->decimal('taxa', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['plano_maquininha_id', 'bandeira_id', 'modalidade'], 'uniq_plano_bandeira_modalidade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plano_maquininha_taxas');
    }
};
