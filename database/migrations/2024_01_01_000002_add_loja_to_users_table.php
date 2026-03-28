<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('loja_id')->nullable()->after('remember_token')->constrained('lojas');
            $table->boolean('ativo')->default(true)->after('loja_id');
            $table->softDeletes();
        });

        Schema::create('usuario_loja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('loja_id')->constrained('lojas')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'loja_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_loja');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['loja_id']);
            $table->dropColumn(['loja_id', 'ativo']);
            $table->dropSoftDeletes();
        });
    }
};
