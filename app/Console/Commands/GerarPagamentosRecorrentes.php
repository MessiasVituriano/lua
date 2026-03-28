<?php

namespace App\Console\Commands;

use App\Models\Pagamento;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GerarPagamentosRecorrentes extends Command
{
    protected $signature = 'pagamentos:gerar-recorrentes';
    protected $description = 'Gera pagamentos recorrentes para o mes atual';

    public function handle(): void
    {
        $mesAtual = Carbon::now()->format('Y-m');

        $recorrentes = Pagamento::where('recorrente', true)
            ->whereNotNull('dia_recorrencia')
            ->get()
            ->groupBy(fn ($p) => $p->loja_id . '-' . $p->descricao . '-' . $p->categoria);

        $criados = 0;

        foreach ($recorrentes as $grupo) {
            $modelo = $grupo->first();
            $dia = min($modelo->dia_recorrencia, Carbon::now()->endOfMonth()->day);
            $dataVencimento = Carbon::createFromFormat('Y-m-d', $mesAtual . '-' . str_pad($dia, 2, '0', STR_PAD_LEFT));

            $jaExiste = Pagamento::where('loja_id', $modelo->loja_id)
                ->where('descricao', $modelo->descricao)
                ->where('categoria', $modelo->categoria)
                ->whereYear('data_vencimento', $dataVencimento->year)
                ->whereMonth('data_vencimento', $dataVencimento->month)
                ->exists();

            if (!$jaExiste) {
                Pagamento::create([
                    'loja_id' => $modelo->loja_id,
                    'fornecedor_id' => $modelo->fornecedor_id,
                    'categoria' => $modelo->categoria,
                    'descricao' => $modelo->descricao,
                    'valor_total' => $modelo->valor_total,
                    'data_vencimento' => $dataVencimento,
                    'status' => 'pendente',
                    'observacao' => $modelo->observacao,
                    'recorrente' => true,
                    'dia_recorrencia' => $modelo->dia_recorrencia,
                ]);
                $criados++;
            }
        }

        $this->info("Pagamentos recorrentes gerados: {$criados}");
    }
}
