<template>
    <div class="produtos-forn px-3 py-2">
        <div v-if="estado.loading" class="text-muted small py-2">
            <span class="spinner-border spinner-border-sm me-2"></span> Carregando produtos...
        </div>
        <div v-else-if="estado.produtos.length === 0" class="text-muted small py-2">
            Nenhum produto para os filtros atuais.
        </div>
        <template v-else>
            <table class="table table-sm mb-0">
                <thead>
                    <tr class="text-muted small">
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th v-if="isAdmin">Custo</th>
                        <th v-if="isAdmin">Venda</th>
                        <th>Estoque</th>
                        <th width="180">Recebido</th>
                        <th width="150">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in estado.produtos" :key="p.id" :class="estoqueBaixo(p) ? 'table-danger' : ''">
                        <td class="fw-semibold">
                            {{ p.nome }}
                            <span v-if="!p.ativo" class="badge badge-inativo ms-1">Inativo</span>
                        </td>
                        <td><span class="badge bg-secondary">{{ categorias[p.categoria] }}</span></td>
                        <td v-if="isAdmin">R$ {{ fmtMoeda(p.valor_custo) }}</td>
                        <td v-if="isAdmin" class="fw-bold">R$ {{ fmtMoeda(p.valor_venda) }}</td>
                        <td>
                            {{ fmtQtd(p, p.estoque_atual) }}
                            <span v-if="estoqueBaixo(p)" class="text-danger" title="Estoque abaixo do mínimo">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </span>
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <input
                                    type="number"
                                    class="form-control"
                                    :step="isRacao(p) ? '0.001' : '1'"
                                    min="0"
                                    v-model="recebido[p.id]"
                                    :placeholder="isRacao(p) ? '0,000' : '0'"
                                    @keyup.enter="emit('registrar')">
                                <span class="input-group-text">{{ isRacao(p) ? 'kg' : 'un' }}</span>
                            </div>
                        </td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-outline-info me-1" @click="emit('detalhe', p.id)" title="Detalhes e movimentações">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button v-if="isAdmin" class="btn btn-sm btn-outline-primary me-1" @click="emit('editar', p.id)" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button v-if="isAdmin" class="btn btn-sm btn-outline-danger" @click="emit('remover', p)" title="Remover">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

        </template>
    </div>
</template>

<script setup>
import { CATEGORIAS_PRODUTO, fmtMoeda, fmtQtd, estoqueBaixo, isRacao } from '../../utils/estoque';

// `recebido` vem da aba: o estado de recebimento nao pode morrer ao recolher a linha.
defineProps({
    estado: { type: Object, required: true },
    recebido: { type: Object, required: true },
    isAdmin: { type: Boolean, default: false },
});
const emit = defineEmits(['detalhe', 'editar', 'remover', 'registrar']);

const categorias = CATEGORIAS_PRODUTO;
</script>

<style scoped>
.produtos-forn {
    background: var(--bs-tertiary-bg, rgba(0, 0, 0, 0.02));
}
</style>
