<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            $table->string('porte', 20)->nullable()->after('tipo');
            $table->string('raca', 80)->nullable()->after('porte');
            $table->unsignedSmallInteger('idade_meses')->nullable()->after('raca');

            $table->index('porte');
            $table->index('idade_meses');
        });
    }

    public function down(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            $table->dropIndex(['porte']);
            $table->dropIndex(['idade_meses']);
            $table->dropColumn(['porte', 'raca', 'idade_meses']);
        });
    }
};
