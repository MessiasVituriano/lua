<?php

namespace Database\Seeders;

use App\Models\BanhoTosaAgendamento;
use App\Models\BanhoTosaCusto;
use App\Models\BanhoTosaServico;
use App\Models\Cliente;
use App\Models\Loja;
use App\Models\Pet;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BanhoTosaSeeder extends Seeder
{
    public function run(): void
    {
        $loja = Loja::first();
        if (!$loja) return;

        // ── Catálogo de Serviços ──────────────────────────────────────────────
        $servicos = [
            [
                'nome'             => 'Banho Simples',
                'categoria'        => 'banho',
                'preco_base'       => 45.00,
                'custo_estimado'   => 12.00,
                'duracao_minutos'  => 60,
                'descricao'        => 'Banho com shampoo, condicionador e secagem.',
            ],
            [
                'nome'             => 'Banho + Tosa Higiênica',
                'categoria'        => 'pacote',
                'preco_base'       => 75.00,
                'custo_estimado'   => 20.00,
                'duracao_minutos'  => 90,
                'descricao'        => 'Banho completo + tosa higiênica (patinhas, focinho e orelha).',
            ],
            [
                'nome'             => 'Tosa na Tesoura',
                'categoria'        => 'tosa',
                'preco_base'       => 90.00,
                'custo_estimado'   => 18.00,
                'duracao_minutos'  => 120,
                'descricao'        => 'Tosa artística completa na tesoura.',
            ],
            [
                'nome'             => 'Tosa na Máquina',
                'categoria'        => 'tosa',
                'preco_base'       => 60.00,
                'custo_estimado'   => 10.00,
                'duracao_minutos'  => 60,
                'descricao'        => 'Tosa curta na máquina.',
            ],
            [
                'nome'             => 'Banho + Tosa Completa',
                'categoria'        => 'pacote',
                'preco_base'       => 120.00,
                'custo_estimado'   => 28.00,
                'duracao_minutos'  => 150,
                'descricao'        => 'Pacote completo: banho + tosa artística + perfume + laço.',
            ],
            [
                'nome'             => 'Hidratação Pelagem',
                'categoria'        => 'extra',
                'preco_base'       => 30.00,
                'custo_estimado'   => 8.00,
                'duracao_minutos'  => 30,
                'descricao'        => 'Hidratação profunda com máscara condicionante.',
            ],
            [
                'nome'             => 'Limpeza de Ouvido',
                'categoria'        => 'extra',
                'preco_base'       => 20.00,
                'custo_estimado'   => 3.00,
                'duracao_minutos'  => 15,
                'descricao'        => 'Limpeza e inspeção das orelhas.',
            ],
        ];

        $created = collect($servicos)->map(function ($s) use ($loja) {
            return BanhoTosaServico::create(array_merge($s, [
                'loja_id' => $loja->id,
                'ativo'   => true,
            ]));
        });

        // ── Agendamentos de demonstração ──────────────────────────────────────
        $clientes = Cliente::where('loja_id', $loja->id)->with('pets')->take(5)->get();
        if ($clientes->isNotEmpty()) {
            $today = Carbon::today();
            $statuses = ['confirmado', 'em_andamento', 'concluido', 'solicitado'];

            $clientes->each(function ($cliente, $i) use ($created, $today, $statuses, $loja) {
                $pet = $cliente->pets->first();
                if (!$pet) return;

                $servico = $created->get($i % $created->count());
                $inicio  = $today->copy()->setHour(9 + $i)->setMinute(0);
                $fim     = $inicio->copy()->addMinutes($servico->duracao_minutos);
                $status  = $statuses[$i % count($statuses)];

                BanhoTosaAgendamento::create([
                    'loja_id'        => $loja->id,
                    'cliente_id'     => $cliente->id,
                    'pet_id'         => $pet->id,
                    'servico_id'     => $servico->id,
                    'data'           => $today->toDateString(),
                    'horario_inicio' => $inicio->format('H:i'),
                    'horario_fim'    => $fim->format('H:i'),
                    'valor_estimado' => $servico->preco_base,
                    'valor_final'    => in_array($status, ['concluido']) ? $servico->preco_base : null,
                    'status'         => $status,
                ]);
            });
        }

        // ── Custos do mês atual ──────────────────────────────────────────────
        $custos = [
            ['descricao' => 'Shampoo profissional 5L',     'tipo' => 'insumo',    'valor' => 85.00],
            ['descricao' => 'Condicionador hidratante 5L',  'tipo' => 'insumo',    'valor' => 70.00],
            ['descricao' => 'Toalhas de microfiber (10un)', 'tipo' => 'insumo',    'valor' => 120.00],
            ['descricao' => 'Aluguel espaço banho/tosa',    'tipo' => 'fixo',      'valor' => 400.00],
            ['descricao' => 'Comissão tosadora maio',       'tipo' => 'comissao',  'valor' => 350.00],
        ];

        foreach ($custos as $c) {
            BanhoTosaCusto::create(array_merge($c, [
                'loja_id'    => $loja->id,
                'data_custo' => Carbon::today()->startOfMonth()->toDateString(),
                'origem'     => 'manual',
            ]));
        }
    }
}
