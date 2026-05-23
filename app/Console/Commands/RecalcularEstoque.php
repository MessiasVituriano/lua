<?php

namespace App\Console\Commands;

use App\Models\Produto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalcularEstoque extends Command
{
    protected $signature = 'estoque:recalcular
                            {--loja= : ID da loja (opcional, recalcula todas se omitido)}
                            {--produto= : ID de um produto específico}
                            {--dry-run : Exibe o que seria alterado sem salvar}';

    protected $description = 'Recalcula estoque_atual de cada produto com base no histórico de movimentações';

    public function handle(): int
    {
        $query = Produto::query()->withTrashed(false);

        if ($lojaId = $this->option('loja')) {
            $query->where('loja_id', $lojaId);
        }

        if ($produtoId = $this->option('produto')) {
            $query->where('id', $produtoId);
        }

        $produtos = $query->get(['id', 'nome', 'loja_id', 'estoque_atual']);

        if ($produtos->isEmpty()) {
            $this->warn('Nenhum produto encontrado.');
            return self::SUCCESS;
        }

        $isDryRun = $this->option('dry-run');

        $this->info(($isDryRun ? '[DRY-RUN] ' : '') . "Recalculando estoque de {$produtos->count()} produto(s)...");
        $this->newLine();

        $headers = ['ID', 'Nome', 'Loja', 'Estoque atual', 'Calculado', 'Diferença'];
        $rows = [];
        $alterados = 0;

        foreach ($produtos as $produto) {
            $calculado = (int) DB::table('movimentacoes_estoque')
                ->where('produto_id', $produto->id)
                ->selectRaw("SUM(CASE WHEN tipo = 'entrada' THEN quantidade ELSE -quantidade END) as saldo")
                ->value('saldo');

            $diferenca = $calculado - (int) $produto->estoque_atual;

            $rows[] = [
                $produto->id,
                mb_strimwidth($produto->nome, 0, 35, '…'),
                $produto->loja_id,
                $produto->estoque_atual,
                $calculado,
                $diferenca > 0 ? "+{$diferenca}" : (string) $diferenca,
            ];

            if ($diferenca !== 0) {
                $alterados++;
                if (!$isDryRun) {
                    $produto->update(['estoque_atual' => $calculado]);
                }
            }
        }

        $this->table($headers, $rows);
        $this->newLine();

        if ($isDryRun) {
            $this->warn("[DRY-RUN] {$alterados} produto(s) seriam atualizados. Use sem --dry-run para aplicar.");
        } else {
            $this->info("{$alterados} produto(s) atualizados com sucesso.");
        }

        return self::SUCCESS;
    }
}
