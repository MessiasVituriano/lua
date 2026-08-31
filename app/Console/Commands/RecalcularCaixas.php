<?php

namespace App\Console\Commands;

use App\Models\CaixaDiario;
use Illuminate\Console\Command;

class RecalcularCaixas extends Command
{
    protected $signature = 'caixa:recalcular
                            {--loja= : ID da loja (opcional, recalcula todas se omitido)}
                            {--de= : Data inicial no formato Y-m-d}
                            {--ate= : Data final no formato Y-m-d}
                            {--dry-run : Exibe o que seria alterado sem salvar}';

    protected $description = 'Recalcula total_entradas, total_saidas e saldo dos caixas a partir dos lancamentos reais';

    public function handle(): int
    {
        $query = CaixaDiario::query()->orderBy('data');

        if ($lojaId = $this->option('loja')) {
            $query->where('loja_id', $lojaId);
        }

        if ($de = $this->option('de')) {
            $query->where('data', '>=', $de);
        }

        if ($ate = $this->option('ate')) {
            $query->where('data', '<=', $ate);
        }

        $caixas = $query->get();

        if ($caixas->isEmpty()) {
            $this->warn('Nenhum caixa encontrado para os filtros informados.');
            return self::SUCCESS;
        }

        $isDryRun = $this->option('dry-run');

        $this->info(($isDryRun ? '[DRY-RUN] ' : '')."Conferindo {$caixas->count()} caixa(s)...");
        $this->newLine();

        $rows = [];
        $alterados = 0;
        $deltaEntradas = 0.0;
        $deltaSaidas = 0.0;
        $deltaSaldo = 0.0;

        foreach ($caixas as $caixa) {
            $totais = $caixa->calcularTotais();

            $difEntradas = round($totais['total_entradas'] - (float) $caixa->total_entradas, 2);
            $difSaidas = round($totais['total_saidas'] - (float) $caixa->total_saidas, 2);
            $difSaldo = round($totais['saldo'] - (float) $caixa->saldo, 2);

            if ($difEntradas === 0.0 && $difSaidas === 0.0 && $difSaldo === 0.0) {
                continue;
            }

            $alterados++;
            $deltaEntradas += $difEntradas;
            $deltaSaidas += $difSaidas;
            $deltaSaldo += $difSaldo;

            $rows[] = [
                $caixa->data->format('d/m/Y'),
                $caixa->loja_id,
                $this->comparacao((float) $caixa->total_entradas, $totais['total_entradas'], $difEntradas),
                $this->comparacao((float) $caixa->total_saidas, $totais['total_saidas'], $difSaidas),
                $this->comparacao((float) $caixa->saldo, $totais['saldo'], $difSaldo),
            ];

            if (!$isDryRun) {
                $caixa->recalcular();
            }
        }

        if ($alterados === 0) {
            $this->info('Todos os caixas ja estao consistentes com os lancamentos. Nada a fazer.');
            return self::SUCCESS;
        }

        $this->table(
            ['Data', 'Loja', 'Entradas (gravado -> real)', 'Saidas (gravado -> real)', 'Saldo (gravado -> real)'],
            $rows
        );
        $this->newLine();

        $this->line('Ajuste total  entradas: '.$this->sinal($deltaEntradas)
            .'  |  saidas: '.$this->sinal($deltaSaidas)
            .'  |  saldo: '.$this->sinal($deltaSaldo));
        $this->newLine();

        if ($isDryRun) {
            $this->warn("[DRY-RUN] {$alterados} caixa(s) seriam corrigidos. Rode sem --dry-run para aplicar.");
        } else {
            $this->info("{$alterados} caixa(s) corrigidos. Metas do periodo foram ressincronizadas.");
        }

        return self::SUCCESS;
    }

    private function comparacao(float $gravado, float $real, float $diferenca): string
    {
        if ($diferenca === 0.0) {
            return number_format($gravado, 2, ',', '.');
        }

        return number_format($gravado, 2, ',', '.')
            .' -> '.number_format($real, 2, ',', '.')
            .' ('.$this->sinal($diferenca).')';
    }

    private function sinal(float $valor): string
    {
        return ($valor > 0 ? '+' : '').number_format($valor, 2, ',', '.');
    }
}
