<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Retirada do dono deixa de ser sangria e passa a ser despesa de
     * pro-labore, para aparecer em "Saidas por categoria" e entrar no
     * resultado do mes como qualquer outra despesa.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE pagamentos DROP CONSTRAINT IF EXISTS pagamentos_categoria_check");
            DB::statement("ALTER TABLE pagamentos ADD CONSTRAINT pagamentos_categoria_check CHECK (categoria IN ('boleto', 'imposto', 'custo_fixo', 'funcionario', 'fornecedor', 'pro_labore', 'outros'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE pagamentos DROP CONSTRAINT IF EXISTS pagamentos_categoria_check");
            DB::statement("ALTER TABLE pagamentos ADD CONSTRAINT pagamentos_categoria_check CHECK (categoria IN ('boleto', 'imposto', 'custo_fixo', 'funcionario', 'fornecedor', 'outros'))");
        }
    }
};
