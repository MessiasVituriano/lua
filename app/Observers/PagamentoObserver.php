<?php

namespace App\Observers;

use App\Models\CaixaDiario;
use App\Models\Pagamento;

/**
 * Mantem caixas_diarios.total_saidas e .saldo sincronizados com os pagamentos.
 *
 * Sem isso os totais do caixa viram uma foto do momento em que o caixa foi
 * mexido pela ultima vez: estornar, editar valor ou mudar a data de um
 * pagamento deixava o dia congelado no valor antigo, e a Meta por saldo
 * (que le esses campos) passava a divergir do dashboard (que consulta os
 * pagamentos ao vivo).
 */
class PagamentoObserver
{
    /**
     * Unicos campos que entram na conta do caixa.
     */
    private const CAMPOS_DO_CAIXA = ['loja_id', 'data_pagamento', 'valor_pago', 'status'];

    public function saved(Pagamento $pagamento): void
    {
        // Um update que nao mexe em nenhum desses campos nao altera o caixa, e
        // ressincronizar a competencia inteira a toa sai caro.
        if (!$pagamento->wasRecentlyCreated && !$pagamento->wasChanged(self::CAMPOS_DO_CAIXA)) {
            return;
        }

        $this->sincronizarCaixas($pagamento);
    }

    public function deleted(Pagamento $pagamento): void
    {
        $this->sincronizarCaixas($pagamento);
    }

    private function sincronizarCaixas(Pagamento $pagamento): void
    {
        $alvos = [[$pagamento->loja_id, $pagamento->data_pagamento]];

        // Ao mudar a data ou a loja, o dia de origem tambem precisa ser
        // recalculado para largar o valor que ficou la.
        if ($pagamento->wasChanged('data_pagamento') || $pagamento->wasChanged('loja_id')) {
            $alvos[] = [$pagamento->getOriginal('loja_id'), $pagamento->getOriginal('data_pagamento')];
        }

        CaixaDiario::recalcularDias($alvos);
    }
}
