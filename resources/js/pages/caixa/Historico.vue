<template>
    <div>
        <!-- Filtros -->
        <div class="card p-3 mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small">Data Inicio</label>
                    <input type="date" class="form-control form-control-sm" v-model="filters.data_inicio">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small">Data Fim</label>
                    <input type="date" class="form-control form-control-sm" v-model="filters.data_fim">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small">Status</label>
                    <select class="form-select form-select-sm" v-model="filters.status">
                        <option value="">Todos</option>
                        <option value="aberto">Aberto</option>
                        <option value="pendente">Pendente</option>
                        <option value="fechado">Fechado</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 d-flex gap-2 flex-wrap">
                    <button class="btn btn-sm btn-lua flex-grow-1 flex-md-grow-0" @click="load"><i class="bi bi-search"></i> Filtrar</button>
                    <button class="btn btn-sm btn-outline-secondary flex-grow-1 flex-md-grow-0" @click="clearFilters">Limpar</button>
                    <a :href="pdfUrl" target="_blank" class="btn btn-sm btn-outline-secondary flex-grow-1 flex-md-grow-0" title="Exportar PDF">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                    <button v-if="userIsAdmin" class="btn btn-sm btn-outline-primary btn-retro flex-grow-1 flex-md-grow-0" @click="abrirModalRetro">
                        <i class="bi bi-calendar-plus"></i> Abrir caixa retroativo
                    </button>
                </div>
            </div>
        </div>

        <!-- Resumo do periodo -->
        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-start border-success border-4">
                    <div class="text-muted small">Total Entradas</div>
                    <div class="fs-5 fw-bold text-success">R$ {{ fmt(totais.total_entradas) }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-start border-danger border-4">
                    <div class="text-muted small">Total Saidas (Pagamentos)</div>
                    <div class="fs-5 fw-bold text-danger">R$ {{ fmt(totais.total_saidas) }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-start border-primary border-4">
                    <div class="text-muted small">Saldo</div>
                    <div class="fs-5 fw-bold" :class="totais.saldo >= 0 ? 'text-primary' : 'text-danger'">
                        R$ {{ fmt(totais.saldo) }}
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-start border-secondary border-4">
                    <div class="text-muted small">Caixas no periodo</div>
                    <div class="fs-5 fw-bold">{{ totais.count || 0 }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Entradas</th>
                            <th>Saidas</th>
                            <th class="d-none d-md-table-cell">Saldo</th>
                            <th>Status</th>
                            <th class="d-none d-lg-table-cell">Fechado por</th>
                            <th width="80">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in caixas" :key="c.id">
                            <td class="fw-semibold">
                                {{ fmtDate(c.data) }}
                                <div class="d-md-none small fw-normal" :class="c.saldo >= 0 ? 'text-primary' : 'text-danger'">
                                    saldo R$ {{ fmt(c.saldo) }}
                                </div>
                            </td>
                            <td class="text-success">R$ {{ fmt(c.total_entradas) }}</td>
                            <td class="text-danger">R$ {{ fmt(c.total_saidas) }}</td>
                            <td :class="c.saldo >= 0 ? 'text-primary' : 'text-danger'" class="fw-bold d-none d-md-table-cell">
                                R$ {{ fmt(c.saldo) }}
                            </td>
                            <td>
                                <span class="badge" :class="statusClass(c.status)">{{ statusLabel(c.status) }}</span>
                            </td>
                            <td class="d-none d-lg-table-cell">{{ c.fechado_por?.name || '-' }}</td>
                            <td class="d-flex gap-1">
                                <router-link :to="{ name: 'caixa.show', params: { id: c.id } }" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-eye"></i>
                                </router-link>
                                <button v-if="c.status === 'aberto'" class="btn btn-sm btn-outline-danger" @click="fechar(c)" title="Fechar caixa">
                                    <i class="bi bi-lock-fill"></i>
                                </button>
                                <button v-if="c.status === 'pendente'" class="btn btn-sm btn-outline-success" @click="autorizar(c)" title="Autorizar fechamento">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                <button v-if="userIsAdmin && c.status !== 'aberto'" class="btn btn-sm btn-outline-primary" @click="reabrir(c)" title="Reabrir caixa">
                                    <i class="bi bi-unlock"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="caixas.length === 0">
                            <td colspan="7" class="text-center text-muted py-4">Nenhum registro encontrado.</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="caixas.length > 0">
                        <tr class="table-light fw-bold">
                            <td>Totais do periodo</td>
                            <td class="text-success">R$ {{ fmt(totais.total_entradas) }}</td>
                            <td class="text-danger">R$ {{ fmt(totais.total_saidas) }}</td>
                            <td :class="totais.saldo >= 0 ? 'text-primary' : 'text-danger'" class="d-none d-md-table-cell">R$ {{ fmt(totais.saldo) }}</td>
                            <td class="text-muted small">{{ totais.count }} caixa(s)</td>
                            <td class="d-none d-lg-table-cell"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Modal: Abrir caixa retroativo -->
        <div class="modal fade" tabindex="-1" ref="modalRetroEl">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Abrir Caixa Retroativo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning small mb-3">
                            <i class="bi bi-exclamation-triangle"></i>
                            Use esta opcao quando o caixa de uma data passada nao foi aberto. Apos abrir, voce podera adicionar entradas e fechar normalmente.
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Data do caixa *</label>
                            <input type="date" class="form-control" v-model="retroData" :max="hojeStr">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-lua" @click="confirmarAbrirRetro" :disabled="retroLoading || !retroData">
                            <span v-if="retroLoading" class="spinner-border spinner-border-sm me-1"></span>
                            Abrir Caixa
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';
import { swalSuccess, swalError, swalConfirmSuccess, swalConfirmInfo } from '../../utils/swal';
const auth = useAuthStore();
const userIsAdmin = computed(() => auth.user?.role === 'admin');
const caixas = ref([]);
const totais = ref({ total_entradas: 0, total_saidas: 0, saldo: 0, count: 0 });
const _now = new Date();
const _mesInicio = new Date(_now.getFullYear(), _now.getMonth(), 1).toISOString().slice(0, 10);
const _mesFim = new Date(_now.getFullYear(), _now.getMonth() + 1, 0).toISOString().slice(0, 10);
const filters = reactive({ data_inicio: _mesInicio, data_fim: _mesFim, status: '' });

const pdfUrl = computed(() => {
    const p = new URLSearchParams();
    if (filters.data_inicio) p.set('data_inicio', filters.data_inicio);
    if (filters.data_fim) p.set('data_fim', filters.data_fim);
    if (filters.status) p.set('status', filters.status);
    return '/api/caixa/historico/pdf?' + p.toString();
});

const modalRetroEl = ref(null);
let modalRetroInstance = null;
const retroData = ref('');
const retroLoading = ref(false);
const hojeStr = new Date().toISOString().slice(0, 10);

async function load() {
    const params = {};
    if (filters.data_inicio) params.data_inicio = filters.data_inicio;
    if (filters.data_fim) params.data_fim = filters.data_fim;
    if (filters.status) params.status = filters.status;
    const { data } = await axios.get('/caixa/historico', { params });
    caixas.value = data.data;
    totais.value = data.totais || { total_entradas: 0, total_saidas: 0, saldo: 0, count: 0 };
}

function clearFilters() {
    filters.data_inicio = _mesInicio;
    filters.data_fim = _mesFim;
    filters.status = '';
    load();
}

function statusClass(s) {
    return { aberto: 'bg-success', pendente: 'bg-warning text-dark', fechado: 'bg-secondary' }[s];
}

function statusLabel(s) {
    return { aberto: 'Aberto', pendente: 'Pendente', fechado: 'Fechado' }[s];
}

async function autorizar(c) {
    if (!(await swalConfirmSuccess('Autorizar fechamento?', 'Caixa de ' + fmtDate(c.data)))) return;
    try {
        await axios.post('/caixa/' + c.id + '/autorizar');
        swalSuccess('Caixa autorizado com sucesso.');
        load();
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao autorizar.');
    }
}

async function fechar(c) {
    const msg = userIsAdmin.value
        ? 'Fechar o caixa de ' + fmtDate(c.data) + ' definitivamente?'
        : 'Enviar o caixa de ' + fmtDate(c.data) + ' para aprovacao do administrador?';
    if (!(await swalConfirmSuccess('Fechar Caixa', msg))) return;
    try {
        const { data } = await axios.post('/caixa/' + c.id + '/fechar');
        swalSuccess(data.status === 'pendente'
            ? 'Caixa enviado para autorizacao do administrador.'
            : 'Caixa fechado com sucesso.'
        );
        load();
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao fechar caixa.');
    }
}

async function reabrir(c) {
    if (!(await swalConfirmInfo('Reabrir caixa?', 'Caixa de ' + fmtDate(c.data) + '. As entradas serao preservadas.'))) return;
    try {
        await axios.post('/caixa/' + c.id + '/reabrir');
        swalSuccess('Caixa reaberto com sucesso.');
        load();
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao reabrir caixa.');
    }
}

function abrirModalRetro() {
    retroData.value = '';
    nextTick(() => {
        if (!modalRetroInstance) {
            modalRetroInstance = new window.bootstrap.Modal(modalRetroEl.value);
        }
        modalRetroInstance.show();
    });
}

async function confirmarAbrirRetro() {
    if (!retroData.value) return;
    retroLoading.value = true;
    try {
        await axios.post('/caixa/abrir', { data: retroData.value });
        modalRetroInstance.hide();
        swalSuccess('Caixa aberto com sucesso. Adicione as entradas e feche normalmente.');
        load();
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao abrir caixa.');
    } finally {
        retroLoading.value = false;
    }
}

function fmt(v) { return Number(v || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1'); }
function fmtDate(d) { const s = typeof d === 'string' ? d.slice(0, 10) : d; return new Date(s + 'T12:00:00').toLocaleDateString('pt-BR'); }

onMounted(load);
</script>

<style scoped>
@media (min-width: 768px) {
    .btn-retro { margin-left: auto; }
}
</style>
