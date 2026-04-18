<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary" @click="changeMonth(-1)">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <h5 class="mb-0">{{ mesLabel }}</h5>
                <button class="btn btn-sm btn-outline-secondary" @click="changeMonth(1)">
                    <i class="bi bi-chevron-right"></i>
                </button>
                <button class="btn btn-sm btn-outline-secondary" @click="goToday">Hoje</button>
            </div>
            <div class="d-flex gap-2">
                <router-link :to="{ name: 'pagamentos.index' }" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-list-ul"></i> Lista
                </router-link>
                <router-link :to="{ name: 'pagamentos.create' }" class="btn btn-lua btn-sm">
                    <i class="bi bi-plus-lg"></i> Novo
                </router-link>
            </div>
        </div>

        <!-- Legenda -->
        <div class="d-flex gap-3 mb-3 flex-wrap">
            <span class="small"><span class="badge bg-danger">&nbsp;</span> Atrasado</span>
            <span class="small"><span class="badge bg-warning text-dark">&nbsp;</span> Pendente</span>
            <span class="small"><span class="badge bg-success">&nbsp;</span> Pago</span>
            <span class="small"><span class="badge bg-info">&nbsp;</span> Parcial</span>
        </div>

        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary"></div>
        </div>

        <!-- Calendario -->
        <div v-else class="card p-2">
            <div class="calendar-grid">
                <div class="cal-header" v-for="d in diasSemana" :key="d">{{ d }}</div>
                <div
                    v-for="(cell, i) in cells"
                    :key="i"
                    class="cal-cell"
                    :class="{ 'cal-other-month': !cell.currentMonth, 'cal-today': cell.isToday }"
                >
                    <div class="cal-day-num">{{ cell.day }}</div>
                    <div class="cal-events">
                        <div
                            v-for="p in cell.pagamentos"
                            :key="p.id"
                            class="cal-event"
                            :class="eventClass(p)"
                            :title="p.descricao + ' - R$ ' + fmt(p.valor_total)"
                            @click="abrirDetalhe(p)"
                        >
                            <span class="cal-event-text">{{ p.descricao }}</span>
                            <span class="cal-event-valor">{{ fmt(p.valor_total) }}</span>
                        </div>
                        <div v-if="cell.extra > 0" class="cal-more text-muted small" @click="abrirDia(cell)">
                            +{{ cell.extra }} mais
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumo do mes -->
        <div class="row g-3 mt-3">
            <div class="col-6 col-md-3">
                <div class="card p-3 text-center">
                    <div class="text-muted small">Total Pendente</div>
                    <div class="fw-bold text-warning">R$ {{ fmt(resumo.pendente) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 text-center">
                    <div class="text-muted small">Total Atrasado</div>
                    <div class="fw-bold text-danger">R$ {{ fmt(resumo.atrasado) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 text-center">
                    <div class="text-muted small">Total Pago</div>
                    <div class="fw-bold text-success">R$ {{ fmt(resumo.pago) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 text-center">
                    <div class="text-muted small">Total Geral</div>
                    <div class="fw-bold">R$ {{ fmt(resumo.total) }}</div>
                </div>
            </div>
        </div>

        <!-- Modal detalhe dia -->
        <div class="modal fade" id="modalDia" tabindex="-1" ref="modalDiaEl">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Pagamentos — {{ diaModalLabel }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div v-for="p in diaModalPagamentos" :key="p.id" class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <div class="fw-semibold">{{ p.descricao }}</div>
                                <small class="text-muted">{{ categorias[p.categoria] }} {{ p.fornecedor?.nome ? '- ' + p.fornecedor.nome : '' }}</small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">R$ {{ fmt(p.valor_total) }}</div>
                                <span class="badge" :class="statusBadge(p.status)">{{ statusLabel(p.status) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';

const loading = ref(true);
const pagamentos = ref([]);
const ano = ref(new Date().getFullYear());
const mes = ref(new Date().getMonth()); // 0-indexed
const diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];
const categorias = { boleto: 'Boleto', imposto: 'Imposto', custo_fixo: 'Custo Fixo', funcionario: 'Funcionario', fornecedor: 'Fornecedor', outros: 'Outros' };
const MAX_EVENTS = 3;

const diaModalPagamentos = ref([]);
const diaModalLabel = ref('');
const modalDiaEl = ref(null);
let modalDiaInstance = null;

const mesLabel = computed(() => {
    const meses = ['Janeiro', 'Fevereiro', 'Marco', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    return meses[mes.value] + ' ' + ano.value;
});

const cells = computed(() => {
    const firstDay = new Date(ano.value, mes.value, 1);
    const lastDay = new Date(ano.value, mes.value + 1, 0);
    const startDow = firstDay.getDay();
    const daysInMonth = lastDay.getDate();
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Map pagamentos by date
    const byDate = {};
    pagamentos.value.forEach(p => {
        const d = p.data_vencimento?.slice(0, 10);
        if (d) {
            if (!byDate[d]) byDate[d] = [];
            byDate[d].push(p);
        }
    });

    const result = [];

    // Previous month days
    const prevLastDay = new Date(ano.value, mes.value, 0).getDate();
    for (let i = startDow - 1; i >= 0; i--) {
        const day = prevLastDay - i;
        const dateStr = fmtIso(ano.value, mes.value - 1, day);
        const pags = byDate[dateStr] || [];
        result.push({ day, currentMonth: false, isToday: false, pagamentos: pags.slice(0, MAX_EVENTS), extra: Math.max(0, pags.length - MAX_EVENTS), allPagamentos: pags, dateStr });
    }

    // Current month days
    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = fmtIso(ano.value, mes.value, day);
        const d = new Date(ano.value, mes.value, day);
        const pags = byDate[dateStr] || [];
        result.push({ day, currentMonth: true, isToday: d.getTime() === today.getTime(), pagamentos: pags.slice(0, MAX_EVENTS), extra: Math.max(0, pags.length - MAX_EVENTS), allPagamentos: pags, dateStr });
    }

    // Next month days
    const remaining = 42 - result.length;
    for (let day = 1; day <= remaining; day++) {
        const dateStr = fmtIso(ano.value, mes.value + 1, day);
        const pags = byDate[dateStr] || [];
        result.push({ day, currentMonth: false, isToday: false, pagamentos: pags.slice(0, MAX_EVENTS), extra: Math.max(0, pags.length - MAX_EVENTS), allPagamentos: pags, dateStr });
    }

    return result;
});

const resumo = computed(() => {
    const r = { pendente: 0, atrasado: 0, pago: 0, parcial: 0, total: 0 };
    pagamentos.value.forEach(p => {
        const v = parseFloat(p.valor_total || 0);
        r.total += v;
        if (p.status === 'pago') r.pago += parseFloat(p.valor_pago || 0);
        else if (p.status === 'atrasado') r.atrasado += v;
        else if (p.status === 'parcial') r.parcial += v;
        else r.pendente += v;
    });
    return r;
});

function fmtIso(y, m, d) {
    const dt = new Date(y, m, d);
    return dt.getFullYear() + '-' + String(dt.getMonth() + 1).padStart(2, '0') + '-' + String(dt.getDate()).padStart(2, '0');
}

function changeMonth(delta) {
    mes.value += delta;
    if (mes.value > 11) { mes.value = 0; ano.value++; }
    if (mes.value < 0) { mes.value = 11; ano.value--; }
    load();
}

function goToday() {
    const now = new Date();
    ano.value = now.getFullYear();
    mes.value = now.getMonth();
    load();
}

function eventClass(p) {
    return {
        'cal-event-atrasado': p.status === 'atrasado',
        'cal-event-pendente': p.status === 'pendente',
        'cal-event-pago': p.status === 'pago',
        'cal-event-parcial': p.status === 'parcial',
    };
}

function statusBadge(s) {
    return { pendente: 'bg-warning text-dark', pago: 'bg-success', atrasado: 'bg-danger', parcial: 'bg-info' }[s];
}

function statusLabel(s) {
    return { pendente: 'Pendente', pago: 'Pago', atrasado: 'Atrasado', parcial: 'Parcial' }[s];
}

function abrirDetalhe(p) {
    diaModalPagamentos.value = [p];
    diaModalLabel.value = fmtDate(p.data_vencimento);
    nextTick(() => {
        if (!modalDiaInstance) modalDiaInstance = new window.bootstrap.Modal(modalDiaEl.value);
        modalDiaInstance.show();
    });
}

function abrirDia(cell) {
    diaModalPagamentos.value = cell.allPagamentos;
    diaModalLabel.value = fmtDate(cell.dateStr);
    nextTick(() => {
        if (!modalDiaInstance) modalDiaInstance = new window.bootstrap.Modal(modalDiaEl.value);
        modalDiaInstance.show();
    });
}

async function load() {
    loading.value = true;
    const dataInicio = fmtIso(ano.value, mes.value, 1);
    const dataFim = fmtIso(ano.value, mes.value + 1, 0);
    try {
        const { data } = await axios.get('/pagamentos', {
            params: { data_inicio: dataInicio, data_fim: dataFim, per_page: 200 },
        });
        pagamentos.value = data.data;
    } catch {} finally { loading.value = false; }
}

function fmt(v) { return Number(v || 0).toFixed(2).replace('.', ','); }
function fmtDate(d) { const s = typeof d === 'string' ? d.slice(0, 10) : d; return new Date(s + 'T12:00:00').toLocaleDateString('pt-BR'); }

onMounted(load);
</script>

<style scoped>
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: var(--lua-input-border, #dee2e6);
    border: 1px solid var(--lua-input-border, #dee2e6);
    border-radius: 8px;
    overflow: hidden;
}
.cal-header {
    background: var(--lua-sidebar, #1e293b);
    color: #fff;
    text-align: center;
    padding: 0.5rem;
    font-size: 0.8rem;
    font-weight: 600;
}
.cal-cell {
    background: var(--lua-card-bg, #fff);
    min-height: 100px;
    padding: 0.3rem;
    position: relative;
}
.cal-other-month {
    opacity: 0.4;
}
.cal-today {
    background: var(--lua-today-bg, #f0f9ff);
}
.cal-today .cal-day-num {
    background: var(--lua-primary, #6366f1);
    color: #fff;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.cal-day-num {
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 0.2rem;
    color: var(--lua-text, #333);
}
.cal-events {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.cal-event {
    font-size: 0.65rem;
    padding: 1px 4px;
    border-radius: 3px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    white-space: nowrap;
    overflow: hidden;
}
.cal-event:hover { opacity: 0.85; }
.cal-event-text {
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
}
.cal-event-valor {
    font-weight: 600;
    margin-left: 4px;
    flex-shrink: 0;
}
.cal-event-atrasado { background: #fee2e2; color: #991b1b; }
.cal-event-pendente { background: #fef3c7; color: #92400e; }
.cal-event-pago { background: #d1fae5; color: #065f46; }
.cal-event-parcial { background: #dbeafe; color: #1e40af; }
.cal-more { cursor: pointer; text-align: center; }
.cal-more:hover { text-decoration: underline; }

@media (max-width: 767.98px) {
    .cal-cell { min-height: 60px; padding: 0.2rem; }
    .cal-event { font-size: 0.55rem; }
    .cal-event-valor { display: none; }
}
</style>
