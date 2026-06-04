<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendario_funcionamento', function (Blueprint $table) {
            $table->time('horario_abertura')->default('08:00:00')->after('ativa');
            $table->time('horario_fechamento')->default('18:00:00')->after('horario_abertura');
        });
    }

    public function down(): void
    {
        Schema::table('calendario_funcionamento', function (Blueprint $table) {
            $table->dropColumn(['horario_abertura', 'horario_fechamento']);
        });
    }
};
