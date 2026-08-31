<?php

namespace App\Observers;

use App\Models\CaixaDiario;
use App\Models\MovimentacaoInterna;

/**
 * Mantem caixas_diarios.saldo sincronizado com as sangrias e aportes.
 *
 * Aprovar, editar ou excluir uma movimentacao muda o saldo do dia, mas os
 * controllers nunca disparavam o recalculo do caixa.
 */
class MovimentacaoInternaObserver
{
    /**
     * Unicos campos que entram na conta do caixa.
     */
    private const CAMPOS_DO_CAIXA = ['loja_id', 'data_movimentacao', 'valor', 'tipo', 'status'];

    public function saved(MovimentacaoInterna $movimentacao): void
    {
        if (!$movimentacao->wasRecentlyCreated && !$movimentacao->wasChanged(self::CAMPOS_DO_CAIXA)) {
            return;
        }

        $this->sincronizarCaixas($movimentacao);
    }

    public function deleted(MovimentacaoInterna $movimentacao): void
    {
        $this->sincronizarCaixas($movimentacao);
    }

    private function sincronizarCaixas(MovimentacaoInterna $movimentacao): void
    {
        $alvos = [];

        if ($this->afetaCaixa($movimentacao->tipo)) {
            $alvos[] = [$movimentacao->loja_id, $movimentacao->data_movimentacao];
        }

        // Mudar tipo, data ou loja pode tirar o valor de um dia e jogar em
        // outro; os dois precisam ser recalculados.
        if ($this->afetaCaixa($movimentacao->getOriginal('tipo'))) {
            $alvos[] = [$movimentacao->getOriginal('loja_id'), $movimentacao->getOriginal('data_movimentacao')];
        }

        CaixaDiario::recalcularDias($alvos);
    }

    /**
     * Só sangria e aporte entram na conta do caixa. Transferencias entre
     * bancos ou entre lojas nao alteram o saldo do dia.
     */
    private function afetaCaixa(?string $tipo): bool
    {
        return in_array($tipo, ['aporte', 'sangria'], true);
    }
}
