<?php

namespace Database\Seeders;

use App\Models\CalendarioFuncionamento;
use App\Models\CaixaDiario;
use App\Models\ExcecaoFuncionamento;
use App\Models\Loja;
use App\Models\MetaMensal;
use App\Services\MetaService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MetasSeeder extends Seeder
{
    public function run(): void
    {
        $loja = Loja::first();
        if (!$loja) {
            $this->command?->warn('MetasSeeder: nenhuma loja encontrada.');
            return;
        }

        $this->seedCalendario($loja->id);
        $this->seedExcecoes($loja->id);
        $this->seedMetas($loja->id);
    }

    private function seedCalendario(int $lojaId): void
    {
        $dias = [
            'segunda' => true,
            'terca' => true,
            'quarta' => true,
            'quinta' => true,
            'sexta' => true,
            'sabado' => true,
            'domingo' => false,
        ];

        foreach ($dias as $diaSemana => $ativa) {
            CalendarioFuncionamento::updateOrCreate(
                ['loja_id' => $lojaId, 'dia_semana' => $diaSemana],
                ['ativa' => $ativa]
            );
        }
    }

    private function seedExcecoes(int $lojaId): void
    {
        $mesAtual = Carbon::now()->startOfMonth();
        $mesAnterior = Carbon::now()->subMonth()->startOfMonth();

        $feriado = $mesAtual->copy()->addDays(10);
        $domingoAberto = $mesAtual->copy()->next(Carbon::SUNDAY);
        $fechamentoPontual = $mesAnterior->copy()->addDays(14);

        ExcecaoFuncionamento::updateOrCreate(
            ['loja_id' => $lojaId, 'data' => $feriado->toDateString()],
            ['tipo' => 'fechado', 'motivo' => 'Feriado local']
        );

        ExcecaoFuncionamento::updateOrCreate(
            ['loja_id' => $lojaId, 'data' => $domingoAberto->toDateString()],
            ['tipo' => 'aberto', 'motivo' => 'Mutirao especial de vendas']
        );

        ExcecaoFuncionamento::updateOrCreate(
            ['loja_id' => $lojaId, 'data' => $fechamentoPontual->toDateString()],
            ['tipo' => 'fechado', 'motivo' => 'Inventario interno']
        );
    }

    private function seedMetas(int $lojaId): void
    {
        /** @var MetaService $metaService */
        $metaService = app(MetaService::class);

        $competencias = collect(range(0, 3))
            ->map(fn (int $m) => Carbon::now()->subMonths(3 - $m)->startOfMonth());

        foreach ($competencias as $competencia) {
            $inicio = $competencia->copy()->startOfMonth();
            $fim = $competencia->copy()->endOfMonth();

            $realizadoVenda = (float) CaixaDiario::where('loja_id', $lojaId)
                ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
                ->sum('total_entradas');

            $realizadoSaldo = (float) CaixaDiario::where('loja_id', $lojaId)
                ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
                ->sum('saldo');

            $baseVenda = $realizadoVenda > 0 ? $realizadoVenda : 30000;
            $baseSaldo = $realizadoSaldo > 0 ? $realizadoSaldo : 18000;

            $metaVenda = round($baseVenda * (rand(95, 120) / 100), 2);
            $metaSaldo = round($baseSaldo * (rand(90, 115) / 100), 2);

            $metaService->upsertMetaMensal(
                $lojaId,
                'venda',
                $competencia->toDateString(),
                $metaVenda,
                'Meta de venda seedada automaticamente'
            );

            $metaService->upsertMetaMensal(
                $lojaId,
                'saldo',
                $competencia->toDateString(),
                $metaSaldo,
                'Meta de saldo seedada automaticamente'
            );

            if ($competencia->lt(Carbon::now()->startOfMonth())) {
                MetaMensal::where('loja_id', $lojaId)
                    ->whereDate('competencia', $competencia->toDateString())
                    ->update(['status' => 'fechada']);
            }
        }

        $this->command?->info('MetasSeeder: metas mensais e diarias geradas com sucesso.');
    }
}