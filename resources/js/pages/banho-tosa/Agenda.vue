<template>
    <div>
        <!-- Cabeçalho com toggle e navegação -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="btn-group">
                <button class="btn btn-sm" :class="view === 'mensal' ? 'btn-lua' : 'btn-outline-secondary'" @click="switchView('mensal')">
                    <i class="bi bi-calendar3 me-1"></i>Mensal
                </button>
                <button class="btn btn-sm" :class="view === 'diario' ? 'btn-lua' : 'btn-outline-secondary'" @click="switchView('diario')">
                    <i class="bi bi-calendar-day me-1"></i>Diário
                </button>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary" @click="navAnterior">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <span class="fw-semibold text-capitalize" style="min-width:180px;text-align:center">
                    {{ view === 'mensal' ? labelMes : labelDia }}
                </span>
                <button class="btn btn-sm btn-outline-secondary" @click="navProximo">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
            <button class="btn btn-sm btn-lua" @click="abrirNovo()">
                <i class="bi bi-plus-lg me-1"></i>Novo Agendamento
            </button>
        </div>

        <!-- ── VISÃO MENSAL ─────────────────────────────────────── -->
        <div v-if="view === 'mensal'">
            <div class="card p-0 overflow-hidden" v-if="!loadingMes">
                <div class="agenda-grid-header">
                    <div v-for="d in diasSemana" :key="d" class="agenda-cell-header">{{ d }}</div>
                </div>
                <div class="agenda-grid-body">
                    <div
                        v-for="(dia, i) in calendarDays"
                        :key="i"
                        class="agenda-cell"
                        :class="{
                            'agenda-cell--outside':  !dia.currentMonth,
                            'agenda-cell--today':     dia.isToday,
                            'agenda-cell--clickable': dia.currentMonth,
                        }"
                        @click="dia.currentMonth && abrirDia(dia.date)"
                    >
                        <span class="agenda-cell-number">{{ dia.day }}</span>
                        <div class="agenda-cell-events">
                            <div
                                v-for="(ag, ai) in (dia.agendamentos || []).slice(0, 3)"
                                :key="ai"
                                class="agenda-event-pill"
                                :class="statusPillClass(ag.status)"
                                :title="`${ag.horario_inicio} – ${ag.pet?.nome || ''} | ${ag.servico?.nome || ''}`"
                            >
                                <span>{{ ag.horario_inicio }} {{ ag.pet?.nome || ag.servico?.nome || '' }}</span>
                            </div>
                            <div v-if="(dia.agendamentos || []).length > 3" class="agenda-event-more">
                                +{{ dia.agendamentos.length - 3 }} mais
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="text-center text-muted py-5">Carregando calendário...</div>
        </div>

        <!-- ── VISÃO DIÁRIA ─────────────────────────────────────── -->
        <div v-if="view === 'diario'">
            <div class="card p-3 mb-4">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small"><i class="bi bi-search me-1"></i>Cliente ou pet</label>
                        <input type="text" class="form-control form-control-sm" v-model="filters.busca"
                            placeholder="Nome do cliente ou pet..." @keyup.enter="loadDia">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Status</label>
                        <select class="form-select form-select-sm" v-model="filters.status" @change="loadDia">
                            <option value="">Todos</option>
                            <option value="solicitado">Solicitado</option>
                            <option value="confirmado">Confirmado</option>
                            <option value="em_andamento">Em andamento</option>
                            <option value="concluido">Concluído</option>
                            <option value="cancelado">Cancelado</option>
                            <option value="faltou">Faltou</option>
                        </select>
                    </div>
                    <div class="col-md-5 d-flex gap-2">
                        <button class="btn btn-sm btn-lua" @click="loadDia"><i class="bi bi-search"></i></button>
                        <button class="btn btn-sm btn-outline-secondary" @click="clearFilters">
                            <i class="bi bi-eraser me-1"></i>Limpar
                        </button>
                        <button class="btn btn-sm btn-outline-secondary ms-auto" @click="irHoje">Hoje</button>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4" v-if="resumo">
                <div class="col-6 col-md-3">
                    <div class="card p-3 text-center">
                        <div class="fs-4 fw-bold text-primary">{{ resumo.total }}</div>
                        <div class="small text-muted">Total do dia</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card p-3 text-center">
                        <div class="fs-4 fw-bold text-success">{{ resumo.concluidos }}</div>
                        <div class="small text-muted">Concluídos</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card p-3 text-center">
                        <div class="fs-4 fw-bold text-warning">{{ resumo.pendentes }}</div>
                        <div class="small text-muted">Pendentes</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card p-3 text-center">
                        <div class="fs-4 fw-bold">{{ fmtMoney(resumo.faturamento) }}</div>
                        <div class="small text-muted">Faturamento</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Horário</th>
                                <th>Cliente</th>
                                <th>Pet</th>
                                <th>Serviço</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th width="180">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="ag in agendamentos" :key="ag.id">
                                <td class="fw-semibold text-nowrap">{{ ag.horario_inicio }} – {{ ag.horario_fim }}</td>
                                <td>{{ ag.cliente?.nome || '-' }}</td>
                                <td>
                                    {{ ag.pet?.nome || '-' }}
                                    <span v-if="ag.pet?.porte" class="text-muted small">({{ porteLabel(ag.pet.porte) }})</span>
                                </td>
                                <td>{{ ag.servico?.nome || ag.observacao || '-' }}</td>
                                <td>{{ ag.valor_final ? fmtMoney(ag.valor_final) : (ag.valor_estimado ? fmtMoney(ag.valor_estimado) : '-') }}</td>
                                <td><span class="badge" :class="statusClass(ag.status)">{{ statusLabel(ag.status) }}</span></td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <button v-if="ag.status === 'solicitado'" class="btn btn-xs btn-outline-success" @click="confirmar(ag)" title="Confirmar">
                                            <i class="bi bi-check"></i>
                                        </button>
                                        <button v-if="ag.status === 'confirmado'" class="btn btn-xs btn-outline-primary" @click="iniciar(ag)" title="Iniciar">
                                            <i class="bi bi-play"></i>
                                        </button>
                                        <button v-if="ag.status === 'em_andamento'" class="btn btn-xs btn-outline-success" @click="concluir(ag)" title="Concluir">
                                            <i class="bi bi-check2-all"></i>
                                        </button>
                                        <button class="btn btn-xs btn-outline-secondary" @click="abrirEdicao(ag)" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button v-if="!['concluido','cancelado'].includes(ag.status)" class="btn btn-xs btn-outline-danger" @click="cancelar(ag)" title="Cancelar">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!loadingDia && agendamentos.length === 0">
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-calendar3 fs-3 d-block mb-2 opacity-50"></i>
                                    Nenhum agendamento para esta data.
                                </td>
                            </tr>
                            <tr v-if="loadingDia">
                                <td colspan="7" class="text-center py-4 text-muted">Carregando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── MODAL AGENDAMENTO ─────────────────────────────────── -->
        <div class="modal fade" ref="modalRef" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-calendar-plus me-2"></i>
                            {{ editingId ? 'Editar Agendamento' : 'Novo Agendamento' }}
                        </h5>
                        <button type="button" class="btn-close" @click="fecharModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small">Data <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" v-model="form.data">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Horário início <span class="text-danger">*</span></label>
                                <input type="time" class="form-control form-control-sm" v-model="form.horario_inicio" @change="onHorarioChange">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Horário fim <span class="text-danger">*</span></label>
                                <input type="time" class="form-control form-control-sm" v-model="form.horario_fim">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small">Cliente</label>
                                <select class="form-select form-select-sm" v-model="form.cliente_id" @change="onClienteChange">
                                    <option value="">-- Selecione o cliente --</option>
                                    <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.nome }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Pet</label>
                                <select class="form-select form-select-sm" v-model="form.pet_id" :disabled="!form.cliente_id || !petsDoCliente.length">
                                    <option value="">-- Selecione o pet --</option>
                                    <option v-for="p in petsDoCliente" :key="p.id" :value="p.id">
                                        {{ p.nome }}{{ p.porte ? ` (${porteLabel(p.porte)})` : '' }}
                                    </option>
                                </select>
                                <div v-if="form.cliente_id && !petsDoCliente.length" class="form-text text-muted">Nenhum pet cadastrado.</div>
                            </div>

                            <div class="col-md-7">
                                <label class="form-label small">Serviço</label>
                                <select class="form-select form-select-sm" v-model="form.servico_id" @change="onServicoChange">
                                    <option value="">-- Selecione o serviço --</option>
                                    <optgroup v-for="cat in categorias" :key="cat.key" :label="cat.label">
                                        <option v-for="s in servicosPorCategoria(cat.key)" :key="s.id" :value="s.id">
                                            {{ s.nome }} ({{ s.duracao_minutos }}min)
                                        </option>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small">Valor estimado (R$)</label>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm" v-model="form.valor_estimado" placeholder="0,00">
                            </div>

                            <div class="col-12">
                                <label class="form-label small">Observações</label>
                                <textarea class="form-control form-control-sm" v-model="form.observacao" rows="2" placeholder="Anotações sobre o agendamento..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="fecharModal">Cancelar</button>
                        <button type="button" class="btn btn-sm btn-lua" @click="salvarAgendamento" :disabled="salvando">
                            <span v-if="salvando" class="spinner-border spinner-border-sm me-1" role="status"></span>
                            {{ editingId ? 'Salvar alterações' : 'Agendar' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, nextTick, reactive, ref, onMounted } from 'vue';
import axios from 'axios';
import { swalConfirmDanger, swalError, swalSuccess } from '../../utils/swal';

// ── Visão ────────────────────────────────────────────────────────────────────
const view = ref('mensal');

// ── Datas base ───────────────────────────────────────────────────────────────
const todayStr = new Date().toISOString().slice(0, 10);
const curDate  = ref(new Date());   // referência para navegação de mês
const selDate  = ref(todayStr);     // data da visão diária

// ── Visão mensal ─────────────────────────────────────────────────────────────
const loadingMes = ref(false);
const mesDados   = ref({});

const diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

const labelMes = computed(() =>
    curDate.value.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' })
);

const calendarDays = computed(() => {
    const ano = curDate.value.getFullYear();
    const mes = curDate.value.getMonth();
    const hoje = todayStr;
    const primeiroDia = new Date(ano, mes, 1);
    const ultimoDia   = new Date(ano, mes + 1, 0);
    const days = [];

    for (let i = 0; i < primeiroDia.getDay(); i++) {
        const dd = new Date(ano, mes, -primeiroDia.getDay() + i + 1);
        days.push({ date: dd.toISOString().slice(0, 10), day: dd.getDate(), currentMonth: false, isToday: false, agendamentos: [] });
    }
    for (let i = 1; i <= ultimoDia.getDate(); i++) {
        const dd   = new Date(ano, mes, i);
        const dStr = dd.toISOString().slice(0, 10);
        days.push({ date: dStr, day: i, currentMonth: true, isToday: dStr === hoje, agendamentos: mesDados.value[dStr] || [] });
    }
    const restante = 7 - (days.length % 7);
    if (restante < 7) {
        for (let i = 1; i <= restante; i++) {
            const dd = new Date(ano, mes + 1, i);
            days.push({ date: dd.toISOString().slice(0, 10), day: dd.getDate(), currentMonth: false, isToday: false, agendamentos: [] });
        }
    }
    return days;
});

async function loadMes() {
    loadingMes.value = true;
    try {
        const ano = curDate.value.getFullYear();
        const mes = curDate.value.getMonth() + 1;
        const { data } = await axios.get('/banho-tosa/agendamentos', { params: { ano, mes } });
        mesDados.value = data.data || {};
    } catch {
        mesDados.value = {};
    } finally {
        loadingMes.value = false;
    }
}

// ── Visão diária ─────────────────────────────────────────────────────────────
const loadingDia   = ref(false);
const agendamentos = ref([]);
const resumo       = ref(null);
const filters      = reactive({ busca: '', status: '' });

const labelDia = computed(() => {
    const [y, m, d] = selDate.value.split('-');
    return new Date(Number(y), Number(m) - 1, Number(d))
        .toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
});

async function loadDia() {
    loadingDia.value = true;
    try {
        const params = { data: selDate.value };
        if (filters.busca)  params.busca  = filters.busca;
        if (filters.status) params.status = filters.status;
        const { data } = await axios.get('/banho-tosa/agendamentos', { params });
        agendamentos.value = data.data   || [];
        resumo.value       = data.resumo ?? null;
    } catch {
        agendamentos.value = [];
    } finally {
        loadingDia.value = false;
    }
}

function clearFilters() {
    filters.busca  = '';
    filters.status = '';
    loadDia();
}

// ── Navegação ─────────────────────────────────────────────────────────────────
function navAnterior() {
    if (view.value === 'mensal') {
        const d = new Date(curDate.value);
        d.setMonth(d.getMonth() - 1);
        curDate.value = d;
        loadMes();
    } else {
        const s = new Date(selDate.value + 'T00:00:00');
        s.setDate(s.getDate() - 1);
        selDate.value = s.toISOString().slice(0, 10);
        loadDia();
    }
}

function navProximo() {
    if (view.value === 'mensal') {
        const d = new Date(curDate.value);
        d.setMonth(d.getMonth() + 1);
        curDate.value = d;
        loadMes();
    } else {
        const s = new Date(selDate.value + 'T00:00:00');
        s.setDate(s.getDate() + 1);
        selDate.value = s.toISOString().slice(0, 10);
        loadDia();
    }
}

function irHoje() {
    selDate.value = todayStr;
    loadDia();
}

function abrirDia(date) {
    selDate.value = date;
    view.value    = 'diario';
    loadDia();
}

function switchView(v) {
    view.value = v;
    if (v === 'mensal') loadMes();
    else loadDia();
}

// ── Ações de status ───────────────────────────────────────────────────────────
async function confirmar(ag) {
    try { await axios.post(`/banho-tosa/agendamentos/${ag.id}/confirmar`); swalSuccess('Confirmado.'); loadDia(); }
    catch { swalError('Erro ao confirmar.'); }
}
async function iniciar(ag) {
    try { await axios.post(`/banho-tosa/agendamentos/${ag.id}/iniciar`); swalSuccess('Iniciado.'); loadDia(); }
    catch { swalError('Erro ao iniciar.'); }
}
async function concluir(ag) {
    try { await axios.post(`/banho-tosa/agendamentos/${ag.id}/concluir`); swalSuccess('Concluído.'); loadDia(); }
    catch { swalError('Erro ao concluir.'); }
}
async function cancelar(ag) {
    if (!(await swalConfirmDanger('Cancelar agendamento?', 'Esta ação não pode ser desfeita.'))) return;
    try { await axios.post(`/banho-tosa/agendamentos/${ag.id}/cancelar`); swalSuccess('Cancelado.'); loadDia(); }
    catch { swalError('Erro ao cancelar.'); }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function fmtMoney(v) { return Number(v || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }); }
function porteLabel(v) { return { pequeno: 'P', medio: 'M', grande: 'G' }[v] || ''; }
function statusLabel(v) { return { solicitado: 'Solicitado', confirmado: 'Confirmado', em_andamento: 'Em andamento', concluido: 'Concluído', cancelado: 'Cancelado', faltou: 'Faltou' }[v] || v; }
function statusClass(v) { return { solicitado: 'bg-secondary', confirmado: 'bg-primary', em_andamento: 'bg-warning text-dark', concluido: 'bg-success', cancelado: 'bg-danger', faltou: 'bg-dark' }[v] || 'bg-secondary'; }
function statusPillClass(v) { return { solicitado: 'pill--solicitado', confirmado: 'pill--confirmado', em_andamento: 'pill--andamento', concluido: 'pill--concluido', cancelado: 'pill--cancelado', faltou: 'pill--faltou' }[v] || ''; }

// ── Modal de agendamento ─────────────────────────────────────────────────────
const modalRef  = ref(null);
let   bsModal   = null;
const editingId = ref(null);
const salvando  = ref(false);
const clientes  = ref([]);
const servicos  = ref([]);

const form = reactive({
    data:           '',
    horario_inicio: '',
    horario_fim:    '',
    cliente_id:     '',
    pet_id:         '',
    servico_id:     '',
    valor_estimado: '',
    observacao:     '',
});

const categorias = [
    { key: 'banho',  label: 'Banho' },
    { key: 'tosa',   label: 'Tosa' },
    { key: 'pacote', label: 'Pacote' },
    { key: 'extra',  label: 'Extra' },
];

const petsDoCliente = computed(() => {
    if (!form.cliente_id) return [];
    const c = clientes.value.find(c => c.id === Number(form.cliente_id));
    return c?.pets || [];
});

function servicosPorCategoria(cat) {
    return servicos.value.filter(s => s.categoria === cat);
}

async function loadDadosModal() {
    const promises = [];
    if (!clientes.value.length) promises.push(
        axios.get('/caixa/clientes-com-pets').then(r => { clientes.value = r.data || []; })
    );
    if (!servicos.value.length) promises.push(
        axios.get('/banho-tosa/servicos').then(r => {
            const list = r.data?.data || r.data || [];
            servicos.value = list.filter(s => s.ativo);
        })
    );
    if (promises.length) await Promise.all(promises).catch(() => {});
}

async function proximoHorarioDisponivel(date) {
    const now      = new Date();
    const hojeStr  = now.toISOString().slice(0, 10);
    const horaAtual = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

    // horário mínimo: hora atual se for hoje, senão começo de expediente
    const minTime = date === hojeStr ? horaAtual : '08:00';

    try {
        // reutiliza dados já carregados se a visão diária já exibe esse dia
        const list = (view.value === 'diario' && selDate.value === date)
            ? agendamentos.value
            : ((await axios.get('/banho-tosa/agendamentos', { params: { data: date } })).data.data || []);

        const maxFim = list
            .filter(a => !['cancelado', 'faltou'].includes(a.status))
            .map(a => (a.horario_fim || '').slice(0, 5))
            .filter(Boolean)
            .sort()
            .at(-1);

        if (maxFim && maxFim > minTime) return maxFim;
    } catch { /* silencioso */ }

    return minTime;
}

async function abrirNovo(date = null) {
    editingId.value = null;
    const targetDate  = date || selDate.value;
    const horaInicio  = await proximoHorarioDisponivel(targetDate);
    Object.assign(form, {
        data:           targetDate,
        horario_inicio: horaInicio,
        horario_fim:    '',
        cliente_id:     '',
        pet_id:         '',
        servico_id:     '',
        valor_estimado: '',
        observacao:     '',
    });
    loadDadosModal();
    nextTick(() => {
        if (!bsModal) bsModal = new window.bootstrap.Modal(modalRef.value);
        bsModal.show();
    });
}

function abrirEdicao(ag) {
    editingId.value = ag.id;
    Object.assign(form, {
        data:           (ag.data || '').slice(0, 10),
        horario_inicio: (ag.horario_inicio || '').slice(0, 5),
        horario_fim:    (ag.horario_fim    || '').slice(0, 5),
        cliente_id:     ag.cliente_id     || '',
        pet_id:         ag.pet_id         || '',
        servico_id:     ag.servico_id     || '',
        valor_estimado: ag.valor_estimado || '',
        observacao:     ag.observacao     || '',
    });
    loadDadosModal();
    nextTick(() => {
        if (!bsModal) bsModal = new window.bootstrap.Modal(modalRef.value);
        bsModal.show();
    });
}

function fecharModal() { bsModal?.hide(); }

function onClienteChange() { form.pet_id = ''; }

function onHorarioChange() {
    if (form.servico_id && form.horario_inicio) onServicoChange();
}

function onServicoChange() {
    const s = servicos.value.find(s => s.id === Number(form.servico_id));
    if (!s) return;
    if (s.preco_base)        form.valor_estimado = s.preco_base;
    if (s.duracao_minutos && form.horario_inicio) {
        const [h, m] = form.horario_inicio.split(':').map(Number);
        const total  = h * 60 + m + s.duracao_minutos;
        form.horario_fim = `${String(Math.floor(total / 60)).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
    }
}

async function salvarAgendamento() {
    if (!form.data || !form.horario_inicio || !form.horario_fim) {
        swalError('Preencha data, horário de início e fim.');
        return;
    }
    salvando.value = true;
    try {
        const payload = {
            data:           form.data,
            horario_inicio: form.horario_inicio,
            horario_fim:    form.horario_fim,
            cliente_id:     form.cliente_id     || null,
            pet_id:         form.pet_id         || null,
            servico_id:     form.servico_id     || null,
            valor_estimado: form.valor_estimado || null,
            observacao:     form.observacao     || null,
        };
        if (editingId.value) {
            await axios.put(`/banho-tosa/agendamentos/${editingId.value}`, payload);
            swalSuccess('Agendamento atualizado!');
        } else {
            await axios.post('/banho-tosa/agendamentos', payload);
            swalSuccess('Agendamento criado!');
        }
        fecharModal();
        view.value === 'diario' ? loadDia() : loadMes();
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao salvar agendamento.');
    } finally {
        salvando.value = false;
    }
}

onMounted(() => loadMes());
</script>

<style scoped>
.agenda-grid-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    border-bottom: 1px solid var(--bs-border-color, #dee2e6);
}
.agenda-cell-header {
    padding: 6px 8px;
    text-align: center;
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--bs-secondary-color, #6c757d);
}
.agenda-grid-body {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
}
.agenda-cell {
    min-height: 100px;
    border-right: 1px solid var(--bs-border-color, #dee2e6);
    border-bottom: 1px solid var(--bs-border-color, #dee2e6);
    padding: 6px;
    transition: background 0.12s;
}
.agenda-cell:nth-child(7n) { border-right: none; }
.agenda-cell--outside      { opacity: 0.3; }
.agenda-cell--clickable    { cursor: pointer; }
.agenda-cell--clickable:hover { background: rgba(98, 70, 234, 0.06); }
.agenda-cell--today .agenda-cell-number {
    background: var(--color-lua, #6246ea);
    color: #fff;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
}
.agenda-cell-number {
    font-size: 0.78rem;
    font-weight: 500;
    display: inline-block;
    margin-bottom: 4px;
}
.agenda-cell-events { display: flex; flex-direction: column; gap: 2px; }
.agenda-event-pill {
    font-size: 0.68rem;
    padding: 1px 5px;
    border-radius: 4px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    max-width: 100%;
}
.agenda-event-more { font-size: 0.65rem; color: var(--bs-secondary-color, #6c757d); padding-left: 2px; }
.pill--solicitado { background: #6c757d22; color: #6c757d; }
.pill--confirmado { background: #0d6efd22; color: #0d6efd; }
.pill--andamento  { background: #ffc10722; color: #856404; }
.pill--concluido  { background: #19875422; color: #198754; }
.pill--cancelado  { background: #dc354522; color: #dc3545; }
.pill--faltou     { background: #21252922; color: #495057; }
[data-bs-theme="dark"] .pill--confirmado { color: #6ea8fe; background: #0d6efd33; }
[data-bs-theme="dark"] .pill--andamento  { color: #ffda6a; background: #ffc10733; }
[data-bs-theme="dark"] .pill--concluido  { color: #75b798; background: #19875433; }
[data-bs-theme="dark"] .pill--cancelado  { color: #ea868f; background: #dc354533; }
[data-bs-theme="dark"] .pill--solicitado { color: #adb5bd; background: #6c757d33; }
[data-bs-theme="dark"] .pill--faltou     { color: #adb5bd; background: #49505733; }
</style>
