<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'atendente'])->default('atendente')->after('ativo');
        });

        // Primeiro: remove constraint do enum atual, adiciona 'pendente'
        DB::statement("ALTER TABLE caixas_diarios DROP CONSTRAINT IF EXISTS caixas_diarios_status_check");
        DB::statement("ALTER TABLE caixas_diarios ADD CONSTRAINT caixas_diarios_status_check CHECK (status IN ('aberto', 'pendente', 'fechado'))");

        Schema::table('caixas_diarios', function (Blueprint $table) {
            $table->foreignId('autorizado_por')->nullable()->after('fechado_em')->constrained('users');
            $table->timestamp('autorizado_em')->nullable()->after('autorizado_por');
        });

        // Atualizar admin existente
        DB::table('users')->where('email', 'admin@lua.com')->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('caixas_diarios', function (Blueprint $table) {
            $table->dropForeign(['autorizado_por']);
            $table->dropColumn(['autorizado_por', 'autorizado_em']);
        });

        DB::statement("ALTER TABLE caixas_diarios DROP CONSTRAINT IF EXISTS caixas_diarios_status_check");
        DB::statement("ALTER TABLE caixas_diarios ADD CONSTRAINT caixas_diarios_status_check CHECK (status IN ('aberto', 'fechado'))");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
