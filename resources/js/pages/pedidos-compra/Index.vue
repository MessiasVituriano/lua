<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div class="d-flex gap-2 flex-wrap">
                <span v-if="totalAtrasados > 0" class="badge bg-danger fs-6">
                    <i class="bi bi-exclamation-triangle"></i> {{ totalAtrasados }} pedido(s) atrasado(s)
                </span>
            </div>
            <router-link :to="{ name: 'pedidos-compra.create' }" class="btn btn-lua flex-shrink-0">
                <i class="bi bi-plus-lg"></i> Novo Pedido
            </router-link>
        </div>

        <!-- Filtros -->
        <div class="card p-3 mb-4">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small">Status</label>
                    <select class="form-select form-select-sm" v-model="filters.status">
                        <option value="">Todos</option>
                        <option value="pendente">Pendente</option>
                        <option value="confirmado">Confirmado</option>
                        <option value="entregue">Entregue</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small">Fornecedor</label>
                    <select class="form-select form-select-sm" v-model="filters.fornecedor_id">
                        <option value="">Todos</option>
                        <option v-for="f in fornecedores" :key="f.id" :value="f.id">{{ f.nome }}</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small">De</label>
                    <input type="date" class="form-control form-control-sm" v-model="filters.data_inicio">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small">Até</label>
                    <input type="date" class="form-control form-control-sm" v-model="filters.data_fim">
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button class="btn btn-sm btn-lua flex-grow-1" @click="load"><i class="bi bi-search"></i> Filtrar</button>
                    <button class="btn btn-sm btn-outline-secondary flex-grow-1" @click="clearFilters">Limpar</button>
                </div>
            </div>
        </div>

        <!-- Pedidos do dia -->
        <div v-if="pedidosDoDia.length > 0" class="mb-4">
            <div class="fw-semibold mb-2 text-muted small text-uppercase">Previsão de entrega hoje</div>
            <div class="d-flex flex-wrap gap-2">
                <span
                    v-for="p in pedidosDoDia"
                    :key="p.id"
                    class="badge"
                    :class="p.atrasado ? 'bg-danger' : 'bg-warning text-dark'"
                >
                    <i class="bi bi-box-seam me-1"></i>
                    #{{ p.id }} {{ p.fornecedor?.nome }} — R$ {{ fmt(p.valor_total) }}
                </span>
            </div>
        </div>

        <!-- Tabela -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Fornecedor</th>
                            <th>Itens</th>
                            <th>Total</th>
                            <th>Estimativa Entrega</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="7" class="text-center py-4 text-muted">Carregando...</td>
                        </tr>
                        <tr v-else-if="pedidos.length === 0">
                            <td colspan="7" class="text-center py-4 text-muted">Nenhum pedido encontrado.</td>
                        </tr>
                        <tr v-for="p in pedidos" :key="p.id" :class="{ 'table-danger': p.atrasado }">
                            <td class="text-muted small">#{{ p.id }}</td>
                            <td>
                                {{ p.fornecedor?.nome }}
                                <span v-if="p.atrasado" class="badge bg-danger ms-1" title="Entrega atrasada">Atrasado</span>
                            </td>
                            <td class="text-muted small">{{ p.itens?.length ?? 0 }} produto(s)</td>
                            <td>R$ {{ fmt(p.valor_total) }}</td>
                            <td>{{ fmtDate(p.data_estimativa_entrega) }}</td>
                            <td>
                                <span class="badge" :class="statusClass(p.status)">{{ statusLabel(p.status) }}</span>
                            </td>
                            <td class="text-end">
                                <router-link :to="{ name: 'pedidos-compra.show', params: { id: p.id } }" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-eye"></i>
                                </router-link>
                                <router-link v-if="p.status === 'pendente'" :to="{ name: 'pedidos-compra.edit', params: { id: p.id } }" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </router-link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const pedidos = ref([]);
const pedidosDoDia = ref([]);
const fornecedores = ref([]);
const totalAtrasados = ref(0);
const loading = ref(false);

function firstDayOfMonth() {
    const d = new Date();
    return new Date(d.getFullYear(), d.getMonth(), 1).toISOString().split('T')[0];
}

function lastDayOfMonth() {
    const d = new Date();
    return new Date(d.getFullYear(), d.getMonth() + 1, 0).toISOString().split('T')[0];
}

const filters = ref({ status: '', fornecedor_id: '', data_inicio: firstDayOfMonth(), data_fim: lastDayOfMonth() });

async function load() {
    loading.value = true;
    try {
        const params = Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v !== ''));
        const { data } = await axios.get('/pedidos-compra', { params });
        pedidos.value = data.data;
        pedidosDoDia.value = data.pedidos_do_dia;
        totalAtrasados.value = data.total_atrasados;
    } finally {
        loading.value = false;
    }
}

function clearFilters() {
    filters.value = { status: '', fornecedor_id: '', data_inicio: firstDayOfMonth(), data_fim: lastDayOfMonth() };
    load();
}

function fmt(v) {
    return Number(v ?? 0).toFixed(2).replace('.', ',');
}

function fmtDate(d) {
    if (!d) return '—';
    const [y, m, day] = d.split('-');
    return `${day}/${m}/${y}`;
}

function statusLabel(s) {
    return { pendente: 'Pendente', confirmado: 'Confirmado', entregue: 'Entregue', cancelado: 'Cancelado' }[s] ?? s;
}

function statusClass(s) {
    return {
        pendente: 'bg-secondary',
        confirmado: 'bg-primary',
        entregue: 'bg-success',
        cancelado: 'bg-danger',
    }[s] ?? 'bg-secondary';
}

onMounted(async () => {
    const { data } = await axios.get('/fornecedores');
    fornecedores.value = data.data ?? data;
    load();
});
</script>
