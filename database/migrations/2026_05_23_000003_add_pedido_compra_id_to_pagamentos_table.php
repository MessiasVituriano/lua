<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagamentos', function (Blueprint $table) {
            $table->foreignId('pedido_compra_id')
                ->nullable()
                ->after('loja_id')
                ->constrained('pedidos_compra')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pagamentos', function (Blueprint $table) {
            $table->dropForeign(['pedido_compra_id']);
            $table->dropColumn('pedido_compra_id');
        });
    }
};
