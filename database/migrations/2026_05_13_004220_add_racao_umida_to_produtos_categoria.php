<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE produtos DROP CONSTRAINT IF EXISTS produtos_categoria_check");
            DB::statement("ALTER TABLE produtos ADD CONSTRAINT produtos_categoria_check CHECK (categoria IN ('racao', 'racao_umida', 'medicamento', 'acessorio', 'higiene', 'petisco'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE produtos DROP CONSTRAINT IF EXISTS produtos_categoria_check");
            DB::statement("ALTER TABLE produtos ADD CONSTRAINT produtos_categoria_check CHECK (categoria IN ('racao', 'medicamento', 'acessorio', 'higiene', 'petisco'))");
        }
    }
};
