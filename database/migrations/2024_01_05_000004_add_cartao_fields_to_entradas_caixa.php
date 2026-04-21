<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entradas_caixa', function (Blueprint $table) {
            $table->foreignId('plano_maquininha_id')->nullable()->after('banco_id')->constrained('planos_maquininha')->nullOnDelete();
            $table->foreignId('bandeira_id')->nullable()->after('plano_maquininha_id')->constrained('bandeiras')->nullOnDelete();
            $table->unsignedTinyInteger('parcelas')->nullable()->after('bandeira_id');
            $table->decimal('taxa_aplicada', 5, 2)->nullable()->after('parcelas');
            $table->decimal('valor_bruto', 10, 2)->nullable()->after('taxa_aplicada');
            $table->boolean('com_antecipacao')->default(false)->after('valor_bruto');
        });
    }

    public function down(): void
    {
        Schema::table('entradas_caixa', function (Blueprint $table) {
            $table->dropForeign(['plano_maquininha_id']);
            $table->dropForeign(['bandeira_id']);
            $table->dropColumn([
                'plano_maquininha_id',
                'bandeira_id',
                'parcelas',
                'taxa_aplicada',
                'valor_bruto',
                'com_antecipacao',
            ]);
        });
    }
};
