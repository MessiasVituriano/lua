<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE produtos DROP CONSTRAINT IF EXISTS produtos_categoria_check");
        DB::statement("ALTER TABLE produtos ADD CONSTRAINT produtos_categoria_check CHECK (categoria IN ('racao', 'medicamento', 'acessorio', 'higiene', 'petisco'))");

        DB::statement("ALTER TABLE fornecedores DROP CONSTRAINT IF EXISTS fornecedores_categoria_check");
        DB::statement("ALTER TABLE fornecedores ADD CONSTRAINT fornecedores_categoria_check CHECK (categoria IN ('racao', 'medicamento', 'acessorio', 'higiene', 'petisco', 'outros'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE produtos DROP CONSTRAINT IF EXISTS produtos_categoria_check");
        DB::statement("ALTER TABLE produtos ADD CONSTRAINT produtos_categoria_check CHECK (categoria IN ('racao', 'medicamento', 'acessorio', 'higiene'))");

        DB::statement("ALTER TABLE fornecedores DROP CONSTRAINT IF EXISTS fornecedores_categoria_check");
        DB::statement("ALTER TABLE fornecedores ADD CONSTRAINT fornecedores_categoria_check CHECK (categoria IN ('racao', 'medicamento', 'acessorio', 'higiene', 'outros'))");
    }
};
