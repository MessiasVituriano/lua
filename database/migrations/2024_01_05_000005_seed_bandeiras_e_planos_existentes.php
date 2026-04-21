<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $bandeirasPadrao = ['VISA', 'MASTERCARD', 'ELO', 'AMEX', 'HIPERCARD'];
        $modalidades = ['debito', 'credito_avista', 'credito_2_6', 'credito_7_12'];

        DB::transaction(function () use ($bandeirasPadrao, $modalidades) {
            $lojas = DB::table('lojas')->pluck('id');

            foreach ($lojas as $lojaId) {
                $bandeiraIds = [];
                foreach ($bandeirasPadrao as $nome) {
                    $existente = DB::table('bandeiras')
                        ->where('loja_id', $lojaId)
                        ->where('nome', $nome)
                        ->value('id');

                    $bandeiraIds[$nome] = $existente ?: DB::table('bandeiras')->insertGetId([
                        'loja_id' => $lojaId,
                        'nome' => $nome,
                        'ativo' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $planoExiste = DB::table('planos_maquininha')->where('loja_id', $lojaId)->exists();
                if ($planoExiste) {
                    continue;
                }

                $planoId = DB::table('planos_maquininha')->insertGetId([
                    'loja_id' => $lojaId,
                    'nome' => 'Padrao',
                    'taxa_antecipacao' => null,
                    'ativo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($bandeiraIds as $bandeiraId) {
                    foreach ($modalidades as $mod) {
                        DB::table('plano_maquininha_taxas')->insert([
                            'plano_maquininha_id' => $planoId,
                            'bandeira_id' => $bandeiraId,
                            'modalidade' => $mod,
                            'taxa' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        // Sem rollback: cascade das tabelas-pai cuida.
    }
};
