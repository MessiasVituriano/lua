<?php

namespace App\Console\Commands;

use App\Models\MovimentacaoInterna;
use App\Models\Pagamento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConverterSangriaEmProLabore extends Command
{
    protected $signature = 'movimentacao:converter-pro-labore
                            {--loja= : ID da loja (opcional, todas se omitido)}
                            {--de= : Data inicial no formato Y-m-d}
                            {--ate= : Data final no formato Y-m-d}
                            {--id=* : IDs especificos de movimentacao (pode repetir)}
                            {--valor-minimo=0 : Ignora sangrias abaixo desse valor}
                            {--dry-run : Exibe o que seria convertido sem gravar}
                            {--force : Aplica sem pedir confirmacao}';

    protected $description = 'Converte sangrias aprovadas em pagamentos de pro-labore, preservando a conta de origem';

    public function handle(): int
    {
        $query = MovimentacaoInterna::with('bancoOrigem')
            ->where('tipo', 'sangria')
            ->where('status', 'aprovada')
            ->orderBy('data_movimentacao');

        if ($lojaId = $this->option('loja')) {
            $query->where('loja_id', $lojaId);
        }

        if ($de = $this->option('de')) {
            $query->whereDate('data_movimentacao', '>=', $de);
        }

        if ($ate = $this->option('ate')) {
            $query->whereDate('data_movimentacao', '<=', $ate);
        }

        if ($ids = $this->option('id')) {
            $query->whereIn('id', $ids);
        }

        $valorMinimo = (float) $this->option('valor-minimo');
        if ($valorMinimo > 0) {
            $query->where('valor', '>=', $valorMinimo);
        }

        $sangrias = $query->get();

        if ($sangrias->isEmpty()) {
            $this->warn('Nenhuma sangria aprovada encontrada para os filtros informados.');
            return self::SUCCESS;
        }

        $isDryRun = $this->option('dry-run');

        $this->info(($isDryRun ? '[DRY-RUN] ' : '')."{$sangrias->count()} sangria(s) selecionada(s):");
        $this->newLine();

        $rows = $sangrias->map(fn (MovimentacaoInterna $s) => [
            $s->id,
            $s->data_movimentacao->format('d/m/Y'),
            number_format((float) $s->valor, 2, ',', '.'),
            mb_strimwidth((string) $s->descricao, 0, 30, '…'),
            $s->bancoOrigem?->nome ?? 'Caixa dinheiro',
            $this->formaPagamento($s),
        ])->all();

        $this->table(
            ['ID', 'Data', 'Valor', 'Descricao', 'Sai de', 'Vira pagamento via'],
            $rows
        );
        $this->newLine();

        $total = (float) $sangrias->sum('valor');
        $this->line('Total a converter: R$ '.number_format($total, 2, ',', '.'));
        $this->newLine();

        if ($isDryRun) {
            $this->warn('[DRY-RUN] Nada foi gravado. Rode sem --dry-run para aplicar.');
            return self::SUCCESS;
        }

        $this->warn('Cada sangria vira um pagamento de pro-labore e a movimentacao original e EXCLUIDA.');

        if (!$this->option('force') && !$this->confirm('Confirma a conversao?', false)) {
            $this->line('Cancelado. Nada foi alterado.');
            return self::SUCCESS;
        }

        $convertidos = 0;

        foreach ($sangrias as $sangria) {
            DB::transaction(function () use ($sangria, &$convertidos) {
                $data = $sangria->data_movimentacao->toDateString();

                Pagamento::create([
                    'loja_id' => $sangria->loja_id,
                    'categoria' => 'pro_labore',
                    'descricao' => $this->descricao($sangria),
                    'valor_total' => $sangria->valor,
                    'valor_pago' => $sangria->valor,
                    'data_vencimento' => $data,
                    'data_pagamento' => $data,
                    'forma_pagamento' => $this->formaPagamento($sangria),
                    'banco_id' => $sangria->banco_origem_id,
                    'status' => 'pago',
                    'observacao' => "Convertido da movimentacao interna #{$sangria->id} (sangria de {$sangria->data_movimentacao->format('d/m/Y')}).",
                ]);

                $sangria->delete();
                $convertidos++;
            });
        }

        $this->newLine();
        $this->info("{$convertidos} sangria(s) convertidas em pro-labore. Caixas e metas foram ressincronizados.");

        return self::SUCCESS;
    }

    /**
     * Preserva a conta de origem: sangria sem banco saiu do caixa fisico,
     * com banco saiu por transferencia. Errar isso desloca o saldo por conta
     * na tela de Movimentacoes.
     */
    private function formaPagamento(MovimentacaoInterna $sangria): string
    {
        return $sangria->banco_origem_id ? 'transferencia' : 'dinheiro';
    }

    private function descricao(MovimentacaoInterna $sangria): string
    {
        $original = trim((string) $sangria->descricao);

        if ($original === '' || mb_stripos($original, 'pró-labore') !== false) {
            return 'Pró-labore';
        }

        return mb_substr('Pró-labore - '.$original, 0, 255);
    }
}
