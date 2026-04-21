<template>
    <div class="dashboard">
        <!-- Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Visão geral</h1>
                <p class="page-subtitle">Acompanhamento financeiro e operacional do período</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters">
            <div class="filter-group">
                <label class="filter-label">Início</label>
                <input type="date" class="form-control form-control-sm" v-model="filters.data_inicio">
            </div>
            <div class="filter-group">
                <label class="filter-label">Fim</label>
                <input type="date" class="form-control form-control-sm" v-model="filters.data_fim">
            </div>
            <div class="filter-group">
                <label class="filter-label">Agrupar</label>
                <select class="form-select form-select-sm" v-model="filters.agrupamento">
                    <option value="dia">Dia</option>
                    <option value="mes">Mês</option>
                </select>
            </div>
            <div class="filter-actions">
                <button class="btn btn-sm btn-lua" @click="load">
                    <Search :size="14" /> Filtrar
                </button>
                <div class="range-chips">
                    <button class="chip" @click="setRange('mes')">Este mês</button>
                    <button class="chip" @click="setRange('3meses')">3 meses</button>
                    <button class="chip" @click="setRange('ano')">Ano</button>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="loading-state">
            <div class="spinner-border text-primary"></div>
        </div>

        <template v-if="d && !loading">
            <!-- KPIs -->
            <div class="kpi-grid">
                <div class="kpi">
                    <div class="kpi-label">
                        <TrendingUp :size="14" class="kpi-icon success" />
                        Total Entradas
                    </div>
                    <div class="kpi-value num-tabular">R$ {{ fmt(d.total_entradas) }}</div>
                    <div class="kpi-delta" :class="varClass(d.total_entradas, d.entradas_anterior)">
                        {{ varPercent(d.total_entradas, d.entradas_anterior) }}
                        <span class="kpi-delta-label">vs período anterior</span>
                    </div>
                </div>

                <div class="kpi">
                    <div class="kpi-label">
                        <TrendingDown :size="14" class="kpi-icon danger" />
                        Total Saídas
                    </div>
                    <div class="kpi-value num-tabular">R$ {{ fmt(d.total_saidas) }}</div>
                    <div class="kpi-delta" :class="varClassInv(d.total_saidas, d.saidas_anterior)">
                        {{ varPercent(d.total_saidas, d.saidas_anterior) }}
                        <span class="kpi-delta-label">vs período anterior</span>
                    </div>
                </div>

                <div class="kpi">
                    <div class="kpi-label">
                        <Wallet :size="14" class="kpi-icon primary" />
                        Saldo
                    </div>
                    <div class="kpi-value num-tabular" :class="d.saldo >= 0 ? 'text-primary' : 'text-danger'">
                        R$ {{ fmt(d.saldo) }}
                    </div>
                    <div class="kpi-delta-label">Receitas menos despesas</div>
                </div>

                <div class="kpi">
                    <div class="kpi-label">
                        <Bell :size="14" class="kpi-icon warning" />
                        Alertas
                    </div>
                    <div class="kpi-alert-list">
                        <div class="kpi-alert-row">
                            <span class="badge bg-danger">{{ d.pagamentos_atrasados }}</span>
                            <span>atrasados</span>
                        </div>
                        <div class="kpi-alert-row">
                            <span class="badge bg-warning">{{ d.pagamentos_pendentes }}</span>
                            <span>pendentes</span>
                        </div>
                        <div class="kpi-alert-row">
                            <span class="badge bg-info">{{ d.estoque_baixo }}</span>
                            <span>estoque baixo</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico principal -->
            <div class="card section-card">
                <div class="section-header">
                    <div>
                        <h3 class="section-title">Entradas vs Saídas</h3>
                        <p class="section-subtitle">Agrupado por {{ filters.agrupamento === 'mes' ? 'mês' : 'dia' }}</p>
                    </div>
                </div>
                <div class="chart-wrap">
                    <Bar v-if="barChartReady" :data="barChartData" :options="barChartOptions" />
                </div>
            </div>

            <!-- Gráficos pizza -->
            <div class="row-split">
                <div class="card section-card">
                    <div class="section-header">
                        <div>
                            <h3 class="section-title">Entradas por forma</h3>
                            <p class="section-subtitle">Distribuição dos recebimentos</p>
                        </div>
                    </div>
                    <div class="chart-wrap small">
                        <Doughnut v-if="Object.keys(d.entradas_por_forma).length" :data="formaChartData" :options="pieOptions" />
                        <p v-else class="empty-state">Sem dados no período.</p>
                    </div>
                </div>
                <div class="card section-card">
                    <div class="section-header">
                        <div>
                            <h3 class="section-title">Saídas por categoria</h3>
                            <p class="section-subtitle">Distribuição das despesas</p>
                        </div>
                    </div>
                    <div class="chart-wrap small">
                        <Doughnut v-if="Object.keys(d.saidas_por_categoria).length" :data="categoriaChartData" :options="pieOptions" />
                        <p v-else class="empty-state">Sem dados no período.</p>
                    </div>
                </div>
            </div>

            <!-- Top 10 melhores e piores -->
            <div class="row-split">
                <div class="card">
                    <div class="card-header section-header-inline">
                        <Trophy :size="16" class="text-success" />
                        <h3 class="section-title">Top 10 melhores dias</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>#</th><th>Data</th><th>Entradas</th><th>Saídas</th><th>Saldo</th></tr></thead>
                            <tbody>
                                <tr v-for="(c, i) in d.top_melhores" :key="i">
                                    <td><span class="rank-num">{{ i + 1 }}</span></td>
                                    <td class="fw-medium">{{ fmtDate(c.data) }}</td>
                                    <td class="text-success num-tabular">R$ {{ fmt(c.total_entradas) }}</td>
                                    <td class="text-danger num-tabular">R$ {{ fmt(c.total_saidas) }}</td>
                                    <td class="fw-semibold num-tabular">R$ {{ fmt(c.saldo) }}</td>
                                </tr>
                                <tr v-if="!d.top_melhores.length"><td colspan="5" class="empty-row">Sem dados.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header section-header-inline">
                        <TrendingDown :size="16" class="text-danger" />
                        <h3 class="section-title">Top 10 piores dias</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>#</th><th>Data</th><th>Entradas</th><th>Saídas</th><th>Saldo</th></tr></thead>
                            <tbody>
                                <tr v-for="(c, i) in d.top_piores" :key="i">
                                    <td><span class="rank-num danger">{{ i + 1 }}</span></td>
                                    <td class="fw-medium">{{ fmtDate(c.data) }}</td>
                                    <td class="text-success num-tabular">R$ {{ fmt(c.total_entradas) }}</td>
                                    <td class="text-danger num-tabular">R$ {{ fmt(c.total_saidas) }}</td>
                                    <td class="fw-semibold num-tabular">R$ {{ fmt(c.saldo) }}</td>
                                </tr>
                                <tr v-if="!d.top_piores.length"><td colspan="5" class="empty-row">Sem dados.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Resumo saidas + maiores despesas -->
            <div class="row-split">
                <div class="card">
                    <div class="card-header section-header-inline">
                        <h3 class="section-title">Saídas por categoria</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Categoria</th><th>Valor</th><th>%</th></tr></thead>
                            <tbody>
                                <tr v-for="(val, cat) in d.saidas_por_categoria" :key="cat">
                                    <td><span class="badge bg-secondary">{{ catLabels[cat] || cat }}</span></td>
                                    <td class="fw-medium num-tabular">R$ {{ fmt(val) }}</td>
                                    <td class="num-tabular">{{ d.total_saidas > 0 ? ((val / d.total_saidas) * 100).toFixed(1) : '0.0' }}%</td>
                                </tr>
                                <tr v-if="!Object.keys(d.saidas_por_categoria).length"><td colspan="3" class="empty-row">Sem dados.</td></tr>
                                <tr v-else class="row-total">
                                    <td>Total</td>
                                    <td class="num-tabular">R$ {{ fmt(d.total_saidas) }}</td>
                                    <td>100%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header section-header-inline">
                        <h3 class="section-title">Top 5 maiores despesas</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Descrição</th><th>Categoria</th><th>Valor</th><th>Data</th></tr></thead>
                            <tbody>
                                <tr v-for="(p, i) in d.maiores_despesas" :key="i">
                                    <td class="fw-medium">{{ p.descricao }}</td>
                                    <td><span class="badge bg-secondary">{{ catLabels[p.categoria] || p.categoria }}</span></td>
                                    <td class="text-danger fw-semibold num-tabular">R$ {{ fmt(p.valor_pago) }}</td>
                                    <td>{{ fmtDate(p.data_pagamento) }}</td>
                                </tr>
                                <tr v-if="!d.maiores_despesas.length"><td colspan="4" class="empty-row">Sem dados.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Movimentacoes internas -->
            <div class="row-split">
                <div class="card">
                    <div class="card-header section-header-inline">
                        <ArrowLeftRight :size="16" class="text-primary" />
                        <h3 class="section-title">Movimentações internas</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Tipo</th><th>Qtd</th><th>Total</th></tr></thead>
                            <tbody>
                                <tr v-for="(label, key) in movTipos" :key="key" v-if="d.movimentacoes_internas[key]">
                                    <td><span class="badge" :class="movBadge(key)">{{ label }}</span></td>
                                    <td class="num-tabular">{{ d.movimentacoes_internas[key].quantidade }}</td>
                                    <td class="fw-medium num-tabular">R$ {{ fmt(d.movimentacoes_internas[key].total) }}</td>
                                </tr>
                                <tr v-if="!Object.keys(d.movimentacoes_internas).length"><td colspan="3" class="empty-row">Nenhuma movimentação no período.</td></tr>
                                <tr v-if="d.movimentacoes_pendentes > 0" class="row-pending">
                                    <td colspan="2">
                                        <Hourglass :size="14" class="me-1" /> Pendentes de aprovação
                                    </td>
                                    <td class="fw-semibold">{{ d.movimentacoes_pendentes }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagamentos proximos + estoque baixo -->
            <div class="row-split">
                <div class="card">
                    <div class="card-header section-header-inline">
                        <AlertTriangle :size="16" class="text-warning" />
                        <h3 class="section-title">Pagamentos próximos / atrasados</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Descrição</th><th>Vencimento</th><th>Valor</th><th>Status</th></tr></thead>
                            <tbody>
                                <tr v-for="p in d.pagamentos_proximos" :key="p.id">
                                    <td class="fw-medium">{{ p.descricao }}</td>
                                    <td>{{ fmtDate(p.data_vencimento) }}</td>
                                    <td class="num-tabular">R$ {{ fmt(p.valor_total) }}</td>
                                    <td><span class="badge" :class="p.status === 'atrasado' ? 'bg-danger' : 'bg-warning'">{{ p.status }}</span></td>
                                </tr>
                                <tr v-if="!d.pagamentos_proximos.length"><td colspan="4" class="empty-row">Nenhum pagamento pendente.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header section-header-inline">
                        <Package :size="16" class="text-danger" />
                        <h3 class="section-title">Produtos com estoque baixo</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Produto</th><th>Atual</th><th>Mínimo</th></tr></thead>
                            <tbody>
                                <tr v-for="p in d.produtos_estoque_baixo" :key="p.id">
                                    <td class="fw-medium">{{ p.nome }}</td>
                                    <td class="text-danger fw-semibold num-tabular">{{ p.estoque_atual }}</td>
                                    <td class="num-tabular">{{ p.estoque_min }}</td>
                                </tr>
                                <tr v-if="!d.produtos_estoque_baixo.length"><td colspan="3" class="empty-row">Estoque OK.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { Bar, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS, CategoryScale, LinearScale, BarElement, ArcElement,
    Title, Tooltip, Legend
} from 'chart.js';
import axios from 'axios';
import {
    TrendingUp, TrendingDown, Wallet, Bell, Trophy, ArrowLeftRight,
    AlertTriangle, Package, Search, Hourglass
} from 'lucide-vue-next';

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
    funcionario: 'Funcionário', fornecedor: 'Fornecedor', outros: 'Outros',
};

const formaLabels = {
    dinheiro: 'Dinheiro', pix: 'PIX', cartao_debito: 'Cartão Débito', cartao_credito: 'Cartão Crédito',
};

const movTipos = {
    transferencia_banco: 'Transf. Banco', sangria: 'Sangria', aporte: 'Aporte', transferencia_loja: 'Transf. Loja',
};

function movBadge(tipo) {
    return { transferencia_banco: 'bg-info', sangria: 'bg-danger', aporte: 'bg-success', transferencia_loja: 'bg-primary' }[tipo] || 'bg-secondary';
}

const formaColors = ['#059669', '#6e56cf', '#d97706', '#dc2626'];
const catColors = ['#6e56cf', '#dc2626', '#d97706', '#059669', '#0284c7', '#78716c'];

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

const isDark = computed(() => document.documentElement.getAttribute('data-bs-theme') === 'dark');
const gridColor = computed(() => isDark.value ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)');
const textColor = computed(() => isDark.value ? '#8a84a3' : '#78716c');

const barChartData = computed(() => {
    if (!d.value) return { labels: [], datasets: [] };
    const labels = d.value.grafico.labels.map(l => {
        if (l.length === 7) {
            const [y, m] = l.split('-');
            return ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'][parseInt(m)-1] + '/' + y.slice(2);
        }
        const dt = new Date(l + 'T12:00:00');
        return dt.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
    });
    return {
        labels,
        datasets: [
            { label: 'Entradas', data: d.value.grafico.entradas, backgroundColor: '#059669', borderRadius: 4, borderSkipped: false },
            { label: 'Saídas', data: d.value.grafico.saidas, backgroundColor: '#dc2626', borderRadius: 4, borderSkipped: false },
        ],
    };
});

const barChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
            align: 'end',
            labels: { color: textColor.value, usePointStyle: true, pointStyle: 'circle', boxWidth: 6, boxHeight: 6, padding: 14, font: { size: 12 } }
        },
        tooltip: {
            backgroundColor: isDark.value ? '#1e1b2d' : '#1c1917',
            padding: 10,
            cornerRadius: 6,
            boxPadding: 4,
            titleFont: { size: 12, weight: 500 },
            bodyFont: { size: 12 },
            callbacks: {
                label: (ctx) => ctx.dataset.label + ': R$ ' + Number(ctx.raw).toFixed(2).replace('.', ','),
            },
        },
    },
    scales: {
        x: { ticks: { color: textColor.value, maxRotation: 0, font: { size: 11 } }, grid: { display: false } },
        y: {
            ticks: {
                color: textColor.value,
                font: { size: 11 },
                callback: (v) => 'R$ ' + (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v),
            },
            grid: { color: gridColor.value, drawBorder: false },
            border: { display: false },
        },
    },
}));

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

const pieOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: '65%',
    plugins: {
        legend: {
            position: 'bottom',
            labels: { color: textColor.value, usePointStyle: true, pointStyle: 'circle', padding: 12, boxWidth: 6, boxHeight: 6, font: { size: 12 } },
        },
        tooltip: {
            backgroundColor: isDark.value ? '#1e1b2d' : '#1c1917',
            padding: 10,
            cornerRadius: 6,
            callbacks: { label: (ctx) => ctx.label + ': R$ ' + Number(ctx.raw).toFixed(2).replace('.', ',') }
        },
    },
}));

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
    if (atual >= anterior) return 'up';
    return 'down';
}

function varClassInv(atual, anterior) {
    if (atual <= anterior) return 'up';
    return 'down';
}

onMounted(load);
</script>

<style scoped>
.dashboard { display: flex; flex-direction: column; gap: 1.25rem; }

/* Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    gap: 1rem;
}
.page-title {
    font-family: 'Inter Tight', 'Inter', sans-serif;
    font-size: 1.5rem;
    font-weight: 600;
    letter-spacing: -0.022em;
    color: var(--lua-text);
    margin: 0 0 0.25rem 0;
}
.page-subtitle {
    font-size: 0.875rem;
    color: var(--lua-text-muted);
    margin: 0;
}

/* Filtros */
.filters {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    align-items: end;
    background: var(--lua-surface);
    border: 1px solid var(--lua-border);
    border-radius: var(--lua-radius);
    padding: 0.875rem 1rem;
}
.filter-group { display: flex; flex-direction: column; gap: 0.25rem; }
.filter-label {
    font-size: 0.6875rem;
    font-weight: 500;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--lua-text-muted);
}
.filter-group .form-control,
.filter-group .form-select { min-width: 140px; }
.filter-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center; margin-left: auto; }
.range-chips { display: flex; gap: 0.375rem; }
.chip {
    background: var(--lua-surface);
    border: 1px solid var(--lua-border);
    color: var(--lua-text-soft);
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.35rem 0.7rem;
    border-radius: 999px;
    cursor: pointer;
    transition: all 0.12s;
}
.chip:hover { background: var(--lua-surface-muted); color: var(--lua-text); border-color: var(--lua-border-strong); }

/* Loading */
.loading-state { display: flex; justify-content: center; padding: 3rem; }

/* KPI Grid */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.875rem;
}
@media (max-width: 900px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 520px) { .kpi-grid { grid-template-columns: 1fr; } }

.kpi {
    background: var(--lua-surface);
    border: 1px solid var(--lua-border);
    border-radius: var(--lua-radius);
    padding: 1rem 1.125rem;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}
.kpi-label {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8125rem;
    color: var(--lua-text-muted);
    font-weight: 500;
}
.kpi-icon.success { color: var(--lua-success); }
.kpi-icon.danger  { color: var(--lua-danger); }
.kpi-icon.primary { color: var(--lua-primary); }
.kpi-icon.warning { color: var(--lua-warning); }

.kpi-value {
    font-family: 'Inter Tight', 'Inter', sans-serif;
    font-size: 1.4rem;
    font-weight: 600;
    letter-spacing: -0.02em;
    color: var(--lua-text);
    line-height: 1.1;
}
.kpi-delta {
    font-size: 0.75rem;
    font-weight: 500;
    display: flex;
    gap: 0.35rem;
    align-items: baseline;
}
.kpi-delta.up { color: var(--lua-success); }
.kpi-delta.down { color: var(--lua-danger); }
.kpi-delta-label {
    font-size: 0.75rem;
    color: var(--lua-text-muted);
    font-weight: 400;
}

.kpi-alert-list { display: flex; flex-direction: column; gap: 0.3rem; }
.kpi-alert-row {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.8125rem;
    color: var(--lua-text-soft);
}

/* Sections */
.section-card { padding: 1.125rem 1.25rem; }
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1rem;
}
.section-header-inline {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.section-title {
    font-family: 'Inter Tight', 'Inter', sans-serif;
    font-size: 0.9375rem;
    font-weight: 600;
    letter-spacing: -0.01em;
    color: var(--lua-text);
    margin: 0;
}
.section-subtitle {
    font-size: 0.8125rem;
    color: var(--lua-text-muted);
    margin: 0.15rem 0 0 0;
}

.chart-wrap { height: 320px; position: relative; }
.chart-wrap.small { height: 280px; }
.empty-state {
    color: var(--lua-text-muted);
    text-align: center;
    padding-top: 4rem;
    font-size: 0.875rem;
}
.empty-row {
    color: var(--lua-text-muted);
    text-align: center;
    padding: 1.25rem !important;
    font-size: 0.8125rem;
}

/* Row split */
.row-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.875rem;
}
.row-split > .card { min-width: 0; }
@media (max-width: 900px) { .row-split { grid-template-columns: 1fr; } }

/* Rank chips para top 10 */
.rank-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 0.4rem;
    font-size: 0.7rem;
    font-weight: 600;
    border-radius: 6px;
    background: var(--lua-success-soft);
    color: var(--lua-success);
}
.rank-num.danger {
    background: var(--lua-danger-soft);
    color: var(--lua-danger);
}

.fw-medium { font-weight: 500; }
.row-total {
    font-weight: 600;
    background: var(--lua-surface-muted);
}
.row-total td { border-bottom: none; }
.row-pending { background: var(--lua-warning-soft); color: var(--lua-warning); }
.row-pending td { border-bottom: none; }
</style>
