<template>
    <div>
        <!-- Filtros -->
        <div class="card p-3 mb-4">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Data Inicio</label>
                    <input type="date" class="form-control form-control-sm" v-model="filters.data_inicio">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Data Fim</label>
                    <input type="date" class="form-control form-control-sm" v-model="filters.data_fim">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Agrupar por</label>
                    <select class="form-select form-select-sm" v-model="filters.agrupamento">
                        <option value="dia">Dia</option>
                        <option value="mes">Mes</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2 flex-wrap">
                    <button class="btn btn-sm btn-lua" @click="load"><i class="bi bi-search"></i> Filtrar</button>
                    <button class="btn btn-sm btn-outline-secondary" @click="setRange('mes')">Este Mes</button>
                    <button class="btn btn-sm btn-outline-secondary" @click="setRange('3meses')">3 Meses</button>
                    <button class="btn btn-sm btn-outline-secondary" @click="setRange('ano')">Este Ano</button>
                </div>
            </div>
        </div>

        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary"></div>
        </div>

        <template v-if="d && !loading">
            <!-- Cards resumo -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="card p-3 border-start border-success border-4">
                        <div class="text-muted small">Total Entradas</div>
                        <div class="fs-5 fw-bold text-success">R$ {{ fmt(d.total_entradas) }}</div>
                        <div class="small" :class="varClass(d.total_entradas, d.entradas_anterior)">
                            {{ varPercent(d.total_entradas, d.entradas_anterior) }} vs periodo anterior
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card p-3 border-start border-danger border-4">
                        <div class="text-muted small">Total Saidas</div>
                        <div class="fs-5 fw-bold text-danger">R$ {{ fmt(d.total_saidas) }}</div>
                        <div class="small" :class="varClassInv(d.total_saidas, d.saidas_anterior)">
                            {{ varPercent(d.total_saidas, d.saidas_anterior) }} vs periodo anterior
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card p-3 border-start border-primary border-4">
                        <div class="text-muted small">Saldo</div>
                        <div class="fs-5 fw-bold" :class="d.saldo >= 0 ? 'text-primary' : 'text-danger'">
                            R$ {{ fmt(d.saldo) }}
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small">Alertas</div>
                                <div class="small"><span class="badge bg-danger">{{ d.pagamentos_atrasados }}</span> atrasados</div>
                                <div class="small"><span class="badge bg-warning text-dark">{{ d.pagamentos_pendentes }}</span> pendentes</div>
                                <div class="small"><span class="badge bg-info">{{ d.estoque_baixo }}</span> estoque baixo</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grafico principal: Entradas vs Saidas -->
            <div class="card p-4 mb-4">
                <h6 class="mb-3">Entradas vs Saidas por {{ filters.agrupamento === 'mes' ? 'Mes' : 'Dia' }}</h6>
                <div style="height: 320px; position: relative;">
                    <Bar v-if="barChartReady" :data="barChartData" :options="barChartOptions" />
                </div>
            </div>

            <!-- Graficos pizza -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card p-4">
                        <h6 class="mb-3">Entradas por Forma de Recebimento</h6>
                        <div style="height: 280px; position: relative;">
                            <Doughnut v-if="Object.keys(d.entradas_por_forma).length" :data="formaChartData" :options="pieOptions" />
                            <p v-else class="text-muted text-center mt-5">Sem dados no periodo.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-4">
                        <h6 class="mb-3">Saidas por Categoria</h6>
                        <div style="height: 280px; position: relative;">
                            <Doughnut v-if="Object.keys(d.saidas_por_categoria).length" :data="categoriaChartData" :options="pieOptions" />
                            <p v-else class="text-muted text-center mt-5">Sem dados no periodo.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top 10 melhores e piores -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-white"><h6 class="mb-0 text-success"><i class="bi bi-trophy-fill"></i> Top 10 Melhores Dias</h6></div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead><tr><th>#</th><th>Data</th><th>Entradas</th><th>Saidas</th><th>Saldo</th></tr></thead>
                                <tbody>
                                    <tr v-for="(c, i) in d.top_melhores" :key="i">
                                        <td><span class="badge bg-success">{{ i + 1 }}</span></td>
                                        <td class="fw-semibold">{{ fmtDate(c.data) }}</td>
                                        <td class="text-success">R$ {{ fmt(c.total_entradas) }}</td>
                                        <td class="text-danger">R$ {{ fmt(c.total_saidas) }}</td>
                                        <td class="fw-bold">R$ {{ fmt(c.saldo) }}</td>
                                    </tr>
                                    <tr v-if="!d.top_melhores.length"><td colspan="5" class="text-center text-muted py-3">Sem dados.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-white"><h6 class="mb-0 text-danger"><i class="bi bi-arrow-down-circle-fill"></i> Top 10 Piores Dias</h6></div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead><tr><th>#</th><th>Data</th><th>Entradas</th><th>Saidas</th><th>Saldo</th></tr></thead>
                                <tbody>
                                    <tr v-for="(c, i) in d.top_piores" :key="i">
                                        <td><span class="badge bg-danger">{{ i + 1 }}</span></td>
                                        <td class="fw-semibold">{{ fmtDate(c.data) }}</td>
                                        <td class="text-success">R$ {{ fmt(c.total_entradas) }}</td>
                                        <td class="text-danger">R$ {{ fmt(c.total_saidas) }}</td>
                                        <td class="fw-bold">R$ {{ fmt(c.saldo) }}</td>
                                    </tr>
                                    <tr v-if="!d.top_piores.length"><td colspan="5" class="text-center text-muted py-3">Sem dados.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumo saidas + maiores despesas -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-white"><h6 class="mb-0">Resumo de Saidas por Categoria</h6></div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead><tr><th>Categoria</th><th>Valor</th><th>%</th></tr></thead>
                                <tbody>
                                    <tr v-for="(val, cat) in d.saidas_por_categoria" :key="cat">
                                        <td><span class="badge bg-secondary">{{ catLabels[cat] || cat }}</span></td>
                                        <td class="fw-semibold">R$ {{ fmt(val) }}</td>
                                        <td>{{ d.total_saidas > 0 ? ((val / d.total_saidas) * 100).toFixed(1) : '0.0' }}%</td>
                                    </tr>
                                    <tr v-if="!Object.keys(d.saidas_por_categoria).length"><td colspan="3" class="text-center text-muted py-3">Sem dados.</td></tr>
                                    <tr v-else class="table-active fw-bold">
                                        <td>Total</td>
                                        <td>R$ {{ fmt(d.total_saidas) }}</td>
                                        <td>100%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-white"><h6 class="mb-0">Top 5 Maiores Despesas</h6></div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead><tr><th>Descricao</th><th>Categoria</th><th>Valor</th><th>Data</th></tr></thead>
                                <tbody>
                                    <tr v-for="(p, i) in d.maiores_despesas" :key="i">
                                        <td class="fw-semibold">{{ p.descricao }}</td>
                                        <td><span class="badge bg-secondary">{{ catLabels[p.categoria] || p.categoria }}</span></td>
                                        <td class="text-danger fw-bold">R$ {{ fmt(p.valor_pago) }}</td>
                                        <td>{{ fmtDate(p.data_pagamento) }}</td>
                                    </tr>
                                    <tr v-if="!d.maiores_despesas.length"><td colspan="4" class="text-center text-muted py-3">Sem dados.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagamentos proximos + estoque baixo -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-exclamation-triangle text-warning"></i> Pagamentos Proximos / Atrasados</h6></div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead><tr><th>Descricao</th><th>Vencimento</th><th>Valor</th><th>Status</th></tr></thead>
                                <tbody>
                                    <tr v-for="p in d.pagamentos_proximos" :key="p.id" :class="p.status === 'atrasado' ? 'table-danger' : 'table-warning'">
                                        <td class="fw-semibold">{{ p.descricao }}</td>
                                        <td>{{ fmtDate(p.data_vencimento) }}</td>
                                        <td>R$ {{ fmt(p.valor_total) }}</td>
                                        <td><span class="badge" :class="p.status === 'atrasado' ? 'bg-danger' : 'bg-warning text-dark'">{{ p.status }}</span></td>
                                    </tr>
                                    <tr v-if="!d.pagamentos_proximos.length"><td colspan="4" class="text-center text-muted py-3">Nenhum pagamento pendente.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-box-seam text-danger"></i> Produtos com Estoque Baixo</h6></div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead><tr><th>Produto</th><th>Atual</th><th>Minimo</th></tr></thead>
                                <tbody>
                                    <tr v-for="p in d.produtos_estoque_baixo" :key="p.id" class="table-danger">
                                        <td class="fw-semibold">{{ p.nome }}</td>
                                        <td class="fw-bold text-danger">{{ p.estoque_atual }}</td>
                                        <td>{{ p.estoque_min }}</td>
                                    </tr>
                                    <tr v-if="!d.produtos_estoque_baixo.length"><td colspan="3" class="text-center text-muted py-3">Estoque OK.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { Bar, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS, CategoryScale, LinearScale, BarElement, ArcElement,
    Title, Tooltip, Legend
} from 'chart.js';
import axios from 'axios';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend);

const d = ref(null);
const loading = ref(true);
const barChartReady = ref(false);

const now = new Date();
const filters = reactive({
    data_inicio: new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10),
    data_fim: new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10),
    agrupamento: 'dia',
});

const catLabels = {
    boleto: 'Boleto', imposto: 'Imposto', custo_fixo: 'Custo Fixo',
    funcionario: 'Funcionario', fornecedor: 'Fornecedor', outros: 'Outros',
};

const formaLabels = {
    dinheiro: 'Dinheiro', pix: 'PIX', cartao_debito: 'Cartao Debito', cartao_credito: 'Cartao Credito',
};

const formaColors = ['#10b981', '#6366f1', '#f59e0b', '#ef4444'];
const catColors = ['#6366f1', '#ef4444', '#f59e0b', '#10b981', '#8b5cf6', '#6b7280'];

function setRange(range) {
    const n = new Date();
    if (range === 'mes') {
        filters.data_inicio = new Date(n.getFullYear(), n.getMonth(), 1).toISOString().slice(0, 10);
        filters.data_fim = new Date(n.getFullYear(), n.getMonth() + 1, 0).toISOString().slice(0, 10);
        filters.agrupamento = 'dia';
    } else if (range === '3meses') {
        filters.data_inicio = new Date(n.getFullYear(), n.getMonth() - 2, 1).toISOString().slice(0, 10);
        filters.data_fim = new Date(n.getFullYear(), n.getMonth() + 1, 0).toISOString().slice(0, 10);
        filters.agrupamento = 'dia';
    } else if (range === 'ano') {
        filters.data_inicio = new Date(n.getFullYear(), 0, 1).toISOString().slice(0, 10);
        filters.data_fim = new Date(n.getFullYear(), 11, 31).toISOString().slice(0, 10);
        filters.agrupamento = 'mes';
    }
    load();
}

async function load() {
    loading.value = true;
    barChartReady.value = false;
    try {
        const { data } = await axios.get('/dashboard', { params: filters });
        d.value = data;
        barChartReady.value = true;
    } catch {} finally { loading.value = false; }
}

// Grafico de barras
const isDark = computed(() => document.documentElement.getAttribute('data-bs-theme') === 'dark');
const gridColor = computed(() => isDark.value ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)');
const textColor = computed(() => isDark.value ? '#9ca3af' : '#6b7280');

const barChartData = computed(() => {
    if (!d.value) return { labels: [], datasets: [] };
    const labels = d.value.grafico.labels.map(l => {
        if (l.length === 7) { // YYYY-MM
            const [y, m] = l.split('-');
            return ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'][parseInt(m)-1] + '/' + y.slice(2);
        }
        const dt = new Date(l + 'T12:00:00');
        return dt.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
    });
    return {
        labels,
        datasets: [
            { label: 'Entradas', data: d.value.grafico.entradas, backgroundColor: '#10b981', borderRadius: 4 },
            { label: 'Saidas', data: d.value.grafico.saidas, backgroundColor: '#ef4444', borderRadius: 4 },
        ],
    };
});

const barChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'top', labels: { color: textColor.value, usePointStyle: true, pointStyle: 'circle' } },
        tooltip: {
            callbacks: {
                label: (ctx) => ctx.dataset.label + ': R$ ' + Number(ctx.raw).toFixed(2).replace('.', ','),
            },
        },
    },
    scales: {
        x: { ticks: { color: textColor.value, maxRotation: 45 }, grid: { color: gridColor.value } },
        y: {
            ticks: {
                color: textColor.value,
                callback: (v) => 'R$ ' + (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v),
            },
            grid: { color: gridColor.value },
        },
    },
}));

// Graficos doughnut
const formaChartData = computed(() => {
    if (!d.value) return { labels: [], datasets: [] };
    const keys = Object.keys(d.value.entradas_por_forma);
    return {
        labels: keys.map(k => formaLabels[k] || k),
        datasets: [{ data: keys.map(k => d.value.entradas_por_forma[k]), backgroundColor: formaColors.slice(0, keys.length), borderWidth: 0 }],
    };
});

const categoriaChartData = computed(() => {
    if (!d.value) return { labels: [], datasets: [] };
    const keys = Object.keys(d.value.saidas_por_categoria);
    return {
        labels: keys.map(k => catLabels[k] || k),
        datasets: [{ data: keys.map(k => d.value.saidas_por_categoria[k]), backgroundColor: catColors.slice(0, keys.length), borderWidth: 0 }],
    };
});

const pieOptions = { responsive: true, maintainAspectRatio: false, plugins: {
    legend: { position: 'bottom', labels: { color: textColor.value, usePointStyle: true, pointStyle: 'circle', padding: 12 } },
    tooltip: { callbacks: { label: (ctx) => ctx.label + ': R$ ' + Number(ctx.raw).toFixed(2).replace('.', ',') } },
}};

// Helpers
function fmt(v) { return Number(v || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1'); }
function fmtDate(dt) {
    if (!dt) return '-';
    const s = typeof dt === 'string' ? dt.slice(0, 10) : dt;
    return new Date(s + 'T12:00:00').toLocaleDateString('pt-BR');
}

function varPercent(atual, anterior) {
    if (!anterior || anterior == 0) return atual > 0 ? '+100%' : '0%';
    const pct = ((atual - anterior) / anterior * 100).toFixed(1);
    return (pct > 0 ? '+' : '') + pct + '%';
}

function varClass(atual, anterior) {
    if (atual >= anterior) return 'text-success';
    return 'text-danger';
}

function varClassInv(atual, anterior) {
    // Para saidas, menos e melhor
    if (atual <= anterior) return 'text-success';
    return 'text-danger';
}

onMounted(load);
</script>
