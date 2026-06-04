<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex gap-2">
                <span v-if="totalPendentes > 0" class="badge bg-warning text-dark fs-6">
                    <i class="bi bi-hourglass-split"></i> {{ totalPendentes }} pendente(s)
                </span>
            </div>
            <router-link :to="{ name: 'movimentacoes.create' }" class="btn btn-lua">
                <i class="bi bi-plus-lg"></i> Nova Movimentacao
            </router-link>
        </div>

        <!-- Fluxo de Caixa Geral (Acumulado) -->
        <div class="card mb-4">
            <div class="geral-header" @click="showGeral = !showGeral">
                <div class="geral-header-info">
                    <span class="geral-header-title">Fluxo de Caixa Geral — Acumulado</span>
                    <span class="geral-header-sub">
                        Totais históricos sem filtro de período
                        <template v-if="dGeral?.desde"> · desde {{ fmtDate(dGeral.desde) }}</template>
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span v-if="dGeral && !showGeral" class="geral-header-preview" :class="dGeral.saldo_geral >= 0 ? 'positive' : 'negative'">
                        R$ {{ fmt(dGeral.saldo_geral) }}
                    </span>
                    <button class="btn btn-sm btn-outline-secondary" @click.stop="loadGeral" :disabled="loadingGeral" title="Atualizar">
                        <span v-if="loadingGeral" class="spinner-border spinner-border-sm"></span>
                        <i v-else class="bi bi-arrow-clockwise"></i>
                    </button>
                    <i class="bi" :class="showGeral ? 'bi-chevron-up' : 'bi-chevron-down'" style="font-size:1rem; color: var(--bs-secondary)"></i>
                </div>
            </div>

            <div v-show="showGeral" class="geral-body">
                <div v-if="loadingGeral" class="text-center py-3">
                    <div class="spinner-border text-primary spinner-border-sm"></div>
                </div>
                <template v-else-if="dGeral">
                    <div class="geral-grid">
                        <div class="geral-card success">
                            <div class="geral-label">Total Vendas (Entradas)</div>
                            <div class="geral-value">R$ {{ fmt(dGeral.total_entradas) }}</div>
                        </div>
                        <div class="geral-card danger">
                            <div class="geral-label">Total Saídas (Pagamentos)</div>
                            <div class="geral-value">R$ {{ fmt(dGeral.total_saidas) }}</div>
                        </div>
                        <div class="geral-card warning">
                            <div class="geral-label">Total Sangrias</div>
                            <div class="geral-value">R$ {{ fmt(dGeral.total_sangrias) }}</div>
                        </div>
                        <div class="geral-card info">
                            <div class="geral-label">Total Aportes</div>
                            <div class="geral-value">R$ {{ fmt(dGeral.total_aportes) }}</div>
                        </div>
                        <div class="geral-card" :class="dGeral.saldo_geral >= 0 ? 'primary' : 'danger'">
                            <div class="geral-label">Saldo Geral Acumulado</div>
                            <div class="geral-value fw-bold">R$ {{ fmt(dGeral.saldo_geral) }}</div>
                            <div class="geral-formula">Vendas − Saídas − Sangrias + Aportes</div>
                        </div>
                    </div>

                    <template v-if="dGeralSaldos">
                        <div class="geral-section-label">Por forma de recebimento</div>
                        <div class="geral-forma-grid">
                            <div class="geral-forma-card" style="border-left-color: #059669">
                                <div class="geral-forma-title"><i class="bi bi-cash-stack"></i> Caixa Dinheiro</div>
                                <div class="geral-forma-value" :class="dGeralSaldos.caixa_dinheiro.saldo >= 0 ? 'positive' : 'negative'">
                                    R$ {{ fmt(dGeralSaldos.caixa_dinheiro.saldo) }}
                                </div>
                                <div class="geral-forma-sub">
                                    Entradas: R$ {{ fmt(dGeralSaldos.caixa_dinheiro.entradas) }} ·
                                    Saídas: R$ {{ fmt(dGeralSaldos.caixa_dinheiro.saidas) }}
                                </div>
                            </div>
                            <div v-if="dGeralSaldos.formas?.pix" class="geral-forma-card" style="border-left-color: #d97706">
                                <div class="geral-forma-title"><i class="bi bi-qr-code"></i> PIX (sem banco)</div>
                                <div class="geral-forma-value" :class="dGeralSaldos.formas.pix.saldo >= 0 ? 'positive' : 'negative'">
                                    R$ {{ fmt(dGeralSaldos.formas.pix.saldo) }}
                                </div>
                                <div class="geral-forma-sub">
                                    Entradas: R$ {{ fmt(dGeralSaldos.formas.pix.entradas) }} ·
                                    Saídas: R$ {{ fmt(dGeralSaldos.formas.pix.saidas) }}
                                </div>
                            </div>
                            <div v-for="b in dGeralSaldos.bancos" :key="b.id" class="geral-forma-card" style="border-left-color: #0284c7" :class="{ 'opacity-75': !b.ativo }">
                                <div class="geral-forma-title">
                                    <i class="bi bi-bank"></i> {{ b.nome }}
                                    <span v-if="!b.ativo" class="badge bg-secondary ms-1" style="font-size:0.65rem">inativo</span>
                                </div>
                                <div class="geral-forma-value" :class="b.saldo >= 0 ? 'positive' : 'negative'">
                                    R$ {{ fmt(b.saldo) }}
                                </div>
                                <div class="geral-forma-sub">
                                    Entradas: R$ {{ fmt(b.entradas) }} ·
                                    Saídas: R$ {{ fmt(b.saidas) }}
                                </div>
                            </div>
                        </div>
                    </template>
                </template>
            </div>
        </div>

        <!-- Saldos atuais -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card p-3 border-start border-primary border-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <div class="text-muted small">Saldo total disponivel</div>
                            <div class="fs-3 fw-bold" :class="saldos.total >= 0 ? 'text-primary' : 'text-danger'">
                                R$ {{ fmt(saldos.total) }}
                            </div>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary" @click="loadSaldos" :disabled="loadingSaldos" title="Atualizar saldos">
                            <span v-if="loadingSaldos" class="spinner-border spinner-border-sm"></span>
                            <i v-else class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card p-3 h-100 border-start border-success border-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small"><i class="bi bi-cash-stack"></i> Caixa Dinheiro</div>
                            <div class="fs-4 fw-bold" :class="saldos.caixa_dinheiro.saldo >= 0 ? 'text-success' : 'text-danger'">
                                R$ {{ fmt(saldos.caixa_dinheiro.saldo) }}
                            </div>
                        </div>
                    </div>
                    <div class="small text-muted mt-2">
                        Entradas: R$ {{ fmt(saldos.caixa_dinheiro.entradas) }} ·
                        Saidas: R$ {{ fmt(saldos.caixa_dinheiro.saidas) }}
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4" v-if="saldos.formas?.banco">
                <div class="card p-3 h-100 border-start border-info border-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small"><i class="bi bi-bank"></i> Caixa Bancos (forma)</div>
                            <div class="fs-4 fw-bold" :class="saldos.formas.banco.saldo >= 0 ? 'text-info' : 'text-danger'">
                                R$ {{ fmt(saldos.formas.banco.saldo) }}
                            </div>
                        </div>
                    </div>
                    <div class="small text-muted mt-2">
                        Entradas: R$ {{ fmt(saldos.formas.banco.entradas) }} ·
                        Saidas: R$ {{ fmt(saldos.formas.banco.saidas) }}
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4" v-if="saldos.formas?.pix">
                <div class="card p-3 h-100 border-start border-warning border-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small"><i class="bi bi-qr-code"></i> PIX (sem banco)</div>
                            <div class="fs-4 fw-bold" :class="saldos.formas.pix.saldo >= 0 ? 'text-warning' : 'text-danger'">
                                R$ {{ fmt(saldos.formas.pix.saldo) }}
                            </div>
                        </div>
                    </div>
                    <div class="small text-muted mt-2">
                        Entradas: R$ {{ fmt(saldos.formas.pix.entradas) }} ·
                        Saidas: R$ {{ fmt(saldos.formas.pix.saidas) }}
                    </div>
                </div>
            </div>
            <div v-for="b in saldos.bancos" :key="b.id" class="col-12 col-md-6 col-lg-4">
                <div class="card p-3 h-100 border-start border-info border-4" :class="{ 'opacity-75': !b.ativo }">
                    <div>
                        <div class="text-muted small">
                            <i class="bi bi-bank"></i> {{ b.nome }}
                            <span v-if="!b.ativo" class="badge bg-secondary ms-1">inativo</span>
                        </div>
                        <div class="fs-4 fw-bold" :class="b.saldo >= 0 ? 'text-info' : 'text-danger'">
                            R$ {{ fmt(b.saldo) }}
                        </div>
                    </div>
                    <div class="small text-muted mt-2">
                        Entradas: R$ {{ fmt(b.entradas) }} ·
                        Saidas: R$ {{ fmt(b.saidas) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card p-3 mb-4">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">Tipo</label>
                    <select class="form-select form-select-sm" v-model="filters.tipo">
                        <option value="">Todos</option>
                        <option v-for="(l, k) in tipos" :key="k" :value="k">{{ l }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Status</label>
                    <select class="form-select form-select-sm" v-model="filters.status">
                        <option value="">Todos</option>
                        <option value="solicitada">Solicitada</option>
                        <option value="aprovada">Aprovada</option>
                        <option value="rejeitada">Rejeitada</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">De</label>
                    <input type="date" class="form-control form-control-sm" v-model="filters.data_inicio">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Ate</label>
                    <input type="date" class="form-control form-control-sm" v-model="filters.data_fim">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-sm btn-lua" @click="applyFilters"><i class="bi bi-search"></i> Filtrar</button>
                    <button class="btn btn-sm btn-outline-secondary" @click="clearFilters">Limpar</button>
                    <a :href="pdfUrl" target="_blank" class="btn btn-sm btn-outline-secondary" title="Exportar PDF">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Descricao</th>
                            <th>Valor</th>
                            <th>Solicitado por</th>
                            <th>Status</th>
                            <th width="180">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in movimentacoes" :key="m.id" :class="rowClass(m)">
                            <td>{{ fmtDate(m.data_movimentacao) }}</td>
                            <td><span class="badge" :class="tipoBadge(m.tipo)">{{ tipos[m.tipo] }}</span></td>
                            <td class="fw-semibold">{{ m.descricao }}</td>
                            <td>R$ {{ fmt(m.valor) }}</td>
                            <td>{{ m.solicitado_por?.name || '-' }}</td>
                            <td>
                                <span class="badge" :class="statusClass(m.status)">{{ statusLabel(m.status) }}</span>
                            </td>
                            <td>
                                <template v-if="isAdmin && m.status === 'solicitada'">
                                    <button class="btn btn-sm btn-outline-success me-1" @click="aprovar(m)" title="Aprovar">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger me-1" @click="abrirRejeitar(m)" title="Rejeitar">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </template>
                                <router-link :to="{ name: 'movimentacoes.edit', params: { id: m.id } }" class="btn btn-sm btn-outline-primary me-1" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </router-link>
                                <button class="btn btn-sm btn-outline-danger" @click="destroy(m)" title="Remover">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="movimentacoes.length === 0">
                            <td colspan="7" class="text-center text-muted py-4">Nenhuma movimentacao encontrada.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Rejeitar -->
        <div class="modal fade" id="modalRejeitar" tabindex="-1" ref="modalEl">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Rejeitar Movimentacao</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" v-if="movSelecionada">
                        <p class="mb-1"><strong>{{ movSelecionada.descricao }}</strong></p>
                        <p class="text-muted small mb-3">
                            {{ tipos[movSelecionada.tipo] }} — R$ {{ fmt(movSelecionada.valor) }}
                        </p>
                        <div class="mb-3">
                            <label class="form-label small">Motivo da rejeicao *</label>
                            <textarea class="form-control" rows="3" v-model="motivoRejeicao" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-danger" @click="confirmarRejeicao" :disabled="rejLoading || !motivoRejeicao.trim()">
                            <span v-if="rejLoading" class="spinner-border spinner-border-sm me-1"></span>
                            Rejeitar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';
import { swalSuccess, swalError, swalConfirmDanger, swalConfirmSuccess } from '../../utils/swal';

const auth = useAuthStore();
const isAdmin = computed(() => auth.user?.role === 'admin');
const dGeral = ref(null);
const dGeralSaldos = ref(null);
const loadingGeral = ref(true);
const showGeral = ref(localStorage.getItem('movim_show_geral') !== 'false');

watch(showGeral, (v) => localStorage.setItem('movim_show_geral', v));

async function loadGeral() {
    loadingGeral.value = true;
    try {
        const [{ data: g }, { data: s }] = await Promise.all([
            axios.get('/dashboard/geral'),
            axios.get('/movimentacoes-internas-saldos'),
        ]);
        dGeral.value = g;
        dGeralSaldos.value = s;
    } catch {} finally { loadingGeral.value = false; }
}

const movimentacoes = ref([]);
const totalPendentes = ref(0);
const tipos = { transferencia_banco: 'Transf. Banco', sangria: 'Sangria', aporte: 'Aporte', transferencia_loja: 'Transf. Loja' };
const _now = new Date();
const _mesInicio = new Date(_now.getFullYear(), _now.getMonth(), 1).toISOString().slice(0, 10);
const _mesFim = new Date(_now.getFullYear(), _now.getMonth() + 1, 0).toISOString().slice(0, 10);
const filters = reactive({ tipo: '', status: '', data_inicio: _mesInicio, data_fim: _mesFim });

const pdfUrl = computed(() => {
    const p = new URLSearchParams();
    if (filters.tipo) p.set('tipo', filters.tipo);
    if (filters.status) p.set('status', filters.status);
    if (filters.data_inicio) p.set('data_inicio', filters.data_inicio);
    if (filters.data_fim) p.set('data_fim', filters.data_fim);
    return '/api/movimentacoes-internas/pdf?' + p.toString();
});
const loadingSaldos = ref(false);
const saldos = reactive({
    bancos: [],
    caixa_dinheiro: { entradas: 0, saidas: 0, saldo: 0 },
    formas: {
        dinheiro: { entradas: 0, saidas: 0, saldo: 0 },
        banco: { entradas: 0, saidas: 0, saldo: 0 },
        pix: { entradas: 0, saidas: 0, saldo: 0 },
    },
    total: 0,
});

const movSelecionada = ref(null);
const motivoRejeicao = ref('');
const rejLoading = ref(false);
const modalEl = ref(null);
let modalInstance = null;

async function load() {
    const params = {};
    Object.entries(filters).forEach(([k, v]) => { if (v) params[k] = v; });
    const { data } = await axios.get('/movimentacoes-internas', { params });
    movimentacoes.value = data.data;
    totalPendentes.value = movimentacoes.value.filter(m => m.status === 'solicitada').length;
}

async function loadSaldos() {
    loadingSaldos.value = true;
    try {
        const params = {};
        if (filters.data_inicio) params.data_inicio = filters.data_inicio;
        if (filters.data_fim) params.data_fim = filters.data_fim;
        const { data } = await axios.get('/movimentacoes-internas-saldos', { params });
        saldos.bancos = data.bancos || [];
        saldos.caixa_dinheiro = data.caixa_dinheiro || { entradas: 0, saidas: 0, saldo: 0 };
        saldos.formas = data.formas || {
            dinheiro: { entradas: 0, saidas: 0, saldo: 0 },
            banco: { entradas: 0, saidas: 0, saldo: 0 },
            pix: { entradas: 0, saidas: 0, saldo: 0 },
        };
        saldos.total = data.total || 0;
    } catch {
        // mantem ultimos valores
    } finally {
        loadingSaldos.value = false;
    }
}

function clearFilters() {
    filters.tipo = '';
    filters.status = '';
    filters.data_inicio = _mesInicio;
    filters.data_fim = _mesFim;
    load();
    loadSaldos();
}

function applyFilters() {
    load();
    loadSaldos();
}

function rowClass(m) {
    if (m.status === 'rejeitada') return 'table-danger';
    if (m.status === 'solicitada') return 'table-warning';
    return '';
}

function tipoBadge(tipo) {
    return {
        transferencia_banco: 'bg-info',
        sangria: 'bg-danger',
        aporte: 'bg-success',
        transferencia_loja: 'bg-primary',
    }[tipo] || 'bg-secondary';
}

function statusClass(s) {
    return { solicitada: 'bg-warning text-dark', aprovada: 'bg-success', rejeitada: 'bg-danger' }[s];
}

function statusLabel(s) {
    return { solicitada: 'Solicitada', aprovada: 'Aprovada', rejeitada: 'Rejeitada' }[s];
}

async function aprovar(m) {
    if (!(await swalConfirmSuccess('Aprovar movimentacao?', m.descricao + ' — R$ ' + fmt(m.valor)))) return;
    try {
        await axios.post('/movimentacoes-internas/' + m.id + '/aprovar');
        swalSuccess('Movimentacao aprovada.');
        load();
        loadSaldos();
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao aprovar.');
    }
}

function abrirRejeitar(m) {
    movSelecionada.value = m;
    motivoRejeicao.value = '';
    nextTick(() => {
        if (!modalInstance) {
            modalInstance = new window.bootstrap.Modal(modalEl.value);
        }
        modalInstance.show();
    });
}

async function confirmarRejeicao() {
    rejLoading.value = true;
    try {
        await axios.post('/movimentacoes-internas/' + movSelecionada.value.id + '/rejeitar', {
            motivo_rejeicao: motivoRejeicao.value,
        });
        modalInstance.hide();
        swalSuccess('Movimentacao rejeitada.');
        load();
        loadSaldos();
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao rejeitar.');
    } finally { rejLoading.value = false; }
}

async function destroy(m) {
    if (!(await swalConfirmDanger('Remover movimentacao?', 'Deseja remover esta movimentacao?'))) return;
    try {
        await axios.delete('/movimentacoes-internas/' + m.id);
        swalSuccess('Movimentacao removida.');
        load();
        loadSaldos();
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao remover.');
    }
}

function fmt(v) { return Number(v || 0).toFixed(2).replace('.', ','); }
function fmtDate(d) { const s = typeof d === 'string' ? d.slice(0, 10) : d; return new Date(s + 'T12:00:00').toLocaleDateString('pt-BR'); }

onMounted(() => {
    load();
    loadSaldos();
    loadGeral();
});
</script>

<style scoped>
/* Fluxo Geral Acumulado */
.geral-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.875rem 1.25rem;
    cursor: pointer;
    border-bottom: 1px solid var(--bs-border-color);
    user-select: none;
    gap: 0.75rem;
}
.geral-header:hover { background: rgba(0, 0, 0, 0.02); }
.geral-header-info { display: flex; flex-direction: column; gap: 0.1rem; }
.geral-header-title { font-weight: 600; font-size: 0.9375rem; }
.geral-header-sub { font-size: 0.8125rem; color: var(--bs-secondary); }
.geral-header-preview { font-size: 1.125rem; font-weight: 700; font-variant-numeric: tabular-nums; }
.geral-header-preview.positive { color: var(--bs-primary); }
.geral-header-preview.negative { color: var(--bs-danger); }

.geral-body { padding: 1.25rem; }

.geral-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}
@media (max-width: 1200px) { .geral-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px)  { .geral-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px)  { .geral-grid { grid-template-columns: 1fr; } }

.geral-card {
    background: var(--bs-light);
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    padding: 0.875rem 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}
.geral-card.success { border-left: 3px solid #059669; }
.geral-card.danger  { border-left: 3px solid #dc2626; }
.geral-card.warning { border-left: 3px solid #d97706; }
.geral-card.info    { border-left: 3px solid #0284c7; }
.geral-card.primary { border-left: 3px solid var(--bs-primary); }
.geral-label {
    font-size: 0.6875rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--bs-secondary);
}
.geral-value {
    font-size: 1.125rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}
.geral-formula { font-size: 0.7rem; color: var(--bs-secondary); margin-top: 0.125rem; }

.geral-section-label {
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--bs-secondary);
    margin-top: 1.25rem;
    margin-bottom: 0.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--bs-border-color);
}
.geral-forma-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.75rem;
}
.geral-forma-card {
    background: var(--bs-light);
    border: 1px solid var(--bs-border-color);
    border-left-width: 3px;
    border-radius: 0.5rem;
    padding: 0.875rem 1rem;
}
.geral-forma-title {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--bs-secondary);
    margin-bottom: 0.35rem;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}
.geral-forma-value {
    font-size: 1.25rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}
.geral-forma-value.positive { color: var(--bs-primary); }
.geral-forma-value.negative { color: var(--bs-danger); }
.geral-forma-sub { font-size: 0.75rem; color: var(--bs-secondary); margin-top: 0.3rem; }
</style>
