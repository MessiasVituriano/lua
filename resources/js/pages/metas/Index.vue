<template>
    <div class="metas-page">
        <div class="page-header">
            <div>
                <h1 class="page-title">Gerenciamento de Metas</h1>
                <p class="page-subtitle">Configuração por loja ativa: {{ auth.lojaAtiva?.nome || 'Loja' }}</p>
            </div>
            <button class="btn btn-sm btn-lua" @click="loadTela" :disabled="loadingAnual">Atualizar</button>
        </div>

        <div class="card section-card">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Meta mensal por ano</h3>
                    <p class="section-subtitle">Clique em editar no mês para ajustar meta mensal e dia a dia</p>
                </div>
                <div class="year-tools">
                    <input class="form-control form-control-sm" type="number" min="2000" max="2100" v-model.number="anoSelecionado" @change="loadAnual">
                    <button class="btn btn-sm btn-outline-secondary" @click="loadAnual" :disabled="loadingAnual">Carregar</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Mês</th>
                            <th>Meta venda</th>
                            <th>Realizado venda</th>
                            <th>% venda</th>
                            <th>Meta saldo</th>
                            <th>Realizado saldo</th>
                            <th>% saldo</th>
                            <th width="90">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="linha in anual.meses" :key="linha.competencia">
                            <td class="fw-semibold">{{ nomeMes(linha.mes) }}/{{ anoSelecionado }}</td>
                            <td>R$ {{ fmtMoney(linha.venda?.valor_meta) }}</td>
                            <td>R$ {{ fmtMoney(linha.venda?.valor_realizado) }}</td>
                            <td>{{ fmtPercent(linha.venda?.percentual_atingido) }}</td>
                            <td>R$ {{ fmtMoney(linha.saldo?.valor_meta) }}</td>
                            <td>R$ {{ fmtMoney(linha.saldo?.valor_realizado) }}</td>
                            <td>{{ fmtPercent(linha.saldo?.percentual_atingido) }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" @click="abrirModalMes(linha)">
                                    Editar
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!anual.meses.length">
                            <td colspan="8" class="text-center text-muted py-3">Sem metas para o ano selecionado.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card section-card">
            <div class="section-header-inline mb-2">
                <h3 class="section-title">Exceção de funcionamento</h3>
                <button class="btn btn-sm btn-outline-secondary ms-auto" @click="salvarExcecao" :disabled="savingExcecao">
                    {{ savingExcecao ? 'Salvando...' : 'Salvar exceção' }}
                </button>
            </div>
            <div class="meta-mini-form">
                <input class="form-control form-control-sm" type="date" v-model="excecao.data">
                <select class="form-select form-select-sm" v-model="excecao.tipo">
                    <option value="fechado">Fechado</option>
                    <option value="aberto">Aberto</option>
                </select>
                <input class="form-control form-control-sm" type="text" placeholder="Motivo" v-model="excecao.motivo">
            </div>
            <div class="table-responsive mt-3">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr><th>Data</th><th>Tipo</th><th>Motivo</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in excecoes" :key="`${e.data}-${e.tipo}`">
                            <td>{{ fmtDate(e.data) }}</td>
                            <td>{{ e.tipo }}</td>
                            <td>{{ e.motivo || '-' }}</td>
                        </tr>
                        <tr v-if="!excecoes.length"><td colspan="3" class="text-muted text-center">Sem exceções</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="feedback" v-if="feedback">{{ feedback }}</div>

        <div class="modal fade" id="modalMetaMes" tabindex="-1" ref="modalMesEl">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar metas de {{ modalMesTitulo }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-grid mb-3">
                            <div>
                                <label class="filter-label">Meta mensal de venda</label>
                                <input class="form-control form-control-sm" type="number" min="0" step="0.01" v-model.number="modalMes.vendaMeta">
                            </div>
                            <div>
                                <label class="filter-label">Meta mensal por saldo</label>
                                <input class="form-control form-control-sm" type="number" min="0" step="0.01" v-model.number="modalMes.saldoMeta">
                            </div>
                            <div class="d-flex align-items-end">
                                <button class="btn btn-sm btn-lua w-100" @click="salvarMensalModal" :disabled="savingMensalModal || loadingModal">
                                    {{ savingMensalModal ? 'Salvando...' : 'Salvar metas mensais' }}
                                </button>
                            </div>
                        </div>

                        <div class="row-split">
                            <div>
                                <h6 class="mb-2">Meta diária de venda</h6>
                                <div class="table-responsive modal-table">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead>
                                            <tr><th>Data</th><th>Meta</th><th>Realizado</th><th></th></tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="dia in modalMes.vendaDias" :key="`mv-${dia.id}`">
                                                <td>{{ fmtDate(dia.data) }}</td>
                                                <td><input class="form-control form-control-sm" type="number" min="0" step="0.01" v-model.number="dia.valor_meta_draft" :disabled="dia.travada || dia.saving"></td>
                                                <td>R$ {{ fmtMoney(dia.valor_realizado) }}</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" @click="salvarDiaModal(dia)" :disabled="dia.travada || dia.saving">
                                                        {{ dia.saving ? '...' : 'Salvar' }}
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-if="!modalMes.vendaDias.length"><td colspan="4" class="text-muted text-center">Sem dias para a competência</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div>
                                <h6 class="mb-2">Meta diária por saldo</h6>
                                <div class="table-responsive modal-table">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead>
                                            <tr><th>Data</th><th>Meta</th><th>Saldo</th><th></th></tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="dia in modalMes.saldoDias" :key="`ms-${dia.id}`">
                                                <td>{{ fmtDate(dia.data) }}</td>
                                                <td><input class="form-control form-control-sm" type="number" min="0" step="0.01" v-model.number="dia.valor_meta_draft" :disabled="dia.travada || dia.saving"></td>
                                                <td>R$ {{ fmtMoney(dia.saldo_diario) }}</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" @click="salvarDiaModal(dia)" :disabled="dia.travada || dia.saving">
                                                        {{ dia.saving ? '...' : 'Salvar' }}
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-if="!modalMes.saldoDias.length"><td colspan="4" class="text-muted text-center">Sem dias para a competência</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();

const loadingAnual = ref(false);
const loadingModal = ref(false);
const savingMensalModal = ref(false);
const savingExcecao = ref(false);
const feedback = ref('');

const anoSelecionado = ref(new Date().getFullYear());
const anual = reactive({
    ano: new Date().getFullYear(),
    meses: [],
});

const excecoes = ref([]);
const excecao = reactive({
    data: '',
    tipo: 'fechado',
    motivo: '',
});

const modalMesEl = ref(null);
let modalMesInstance = null;

const modalMes = reactive({
    competencia: '',
    vendaMeta: 0,
    saldoMeta: 0,
    vendaDias: [],
    saldoDias: [],
});

const modalMesTitulo = computed(() => {
    if (!modalMes.competencia) return '-';
    const [ano, mes] = modalMes.competencia.slice(0, 10).split('-');
    return `${nomeMes(Number(mes))}/${ano}`;
});

async function loadTela() {
    await Promise.all([loadAnual(), loadExcecoes()]);
}

async function loadAnual() {
    loadingAnual.value = true;
    try {
        const { data } = await axios.get('/metas/anual', { params: { ano: anoSelecionado.value } });
        anual.ano = data.ano || anoSelecionado.value;
        anual.meses = data.meses || [];
        feedback.value = '';
    } catch (error) {
        feedback.value = error?.response?.data?.message || 'Falha ao carregar metas anuais.';
    } finally {
        loadingAnual.value = false;
    }
}

async function loadExcecoes() {
    try {
        const { data } = await axios.get('/metas');
        excecoes.value = data.excecoes || [];
    } catch {}
}

async function abrirModalMes(linha) {
    await carregarCompetencia(linha.competencia);
    nextTick(() => {
        if (!modalMesInstance) modalMesInstance = new window.bootstrap.Modal(modalMesEl.value);
        modalMesInstance.show();
    });
}

async function carregarCompetencia(competencia) {
    loadingModal.value = true;
    try {
        const { data } = await axios.get('/metas', { params: { competencia } });

        modalMes.competencia = data.competencia;
        modalMes.vendaMeta = Number(data.metas?.venda?.valor_meta || data.metas?.venda?.valor_meta_sugerido || 0);
        modalMes.saldoMeta = Number(data.metas?.saldo?.valor_meta || data.metas?.saldo?.valor_meta_sugerido || 0);
        modalMes.vendaDias = prepararDias(data.metas?.venda?.dias || []);
        modalMes.saldoDias = prepararDias(data.metas?.saldo?.dias || []);
        feedback.value = '';
    } catch (error) {
        feedback.value = error?.response?.data?.message || 'Falha ao abrir mês para edição.';
    } finally {
        loadingModal.value = false;
    }
}

function prepararDias(dias) {
    return dias.map((dia) => ({
        ...dia,
        valor_meta_draft: Number(dia.valor_meta || 0),
        saving: false,
    }));
}

async function salvarMensalModal() {
    if (!modalMes.competencia) return;

    savingMensalModal.value = true;
    try {
        await axios.post('/metas', {
            tipo: 'venda',
            competencia: modalMes.competencia,
            valor_meta: Number(modalMes.vendaMeta || 0),
        });
        await axios.post('/metas', {
            tipo: 'saldo',
            competencia: modalMes.competencia,
            valor_meta: Number(modalMes.saldoMeta || 0),
        });

        await Promise.all([carregarCompetencia(modalMes.competencia), loadAnual()]);
        feedback.value = 'Metas mensais salvas com sucesso.';
    } catch (error) {
        feedback.value = error?.response?.data?.message || 'Falha ao salvar metas mensais.';
    } finally {
        savingMensalModal.value = false;
    }
}

async function salvarDiaModal(dia) {
    if (dia.travada) return;

    dia.saving = true;
    try {
        await axios.post(`/metas/dias/${dia.id}`, {
            valor_meta: Number(dia.valor_meta_draft || 0),
        });

        await Promise.all([carregarCompetencia(modalMes.competencia), loadAnual()]);
        feedback.value = 'Meta diária atualizada.';
    } catch (error) {
        feedback.value = error?.response?.data?.message || 'Falha ao atualizar meta diária.';
    } finally {
        dia.saving = false;
    }
}

async function salvarExcecao() {
    if (!excecao.data) return;
    savingExcecao.value = true;
    try {
        await axios.post('/metas/excecao', excecao);
        feedback.value = 'Exceção registrada.';
        excecao.data = '';
        excecao.tipo = 'fechado';
        excecao.motivo = '';
        await Promise.all([loadAnual(), loadExcecoes()]);
    } catch (error) {
        feedback.value = error?.response?.data?.message || 'Falha ao salvar exceção.';
    } finally {
        savingExcecao.value = false;
    }
}

function nomeMes(mesNumero) {
    const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    return meses[(mesNumero || 1) - 1] || '-';
}

function fmtPercent(value) {
    return `${Number(value || 0).toFixed(2).replace('.', ',')}%`;
}

function fmtMoney(value) {
    return Number(value || 0).toFixed(2).replace('.', ',');
}

function fmtDate(date) {
    if (!date) return '-';
    const base = typeof date === 'string' ? date.slice(0, 10) : date;
    const parsed = typeof base === 'string' ? new Date(base + 'T12:00:00') : new Date(base);
    return Number.isNaN(parsed.getTime()) ? '-' : parsed.toLocaleDateString('pt-BR');
}

onMounted(loadTela);
</script>

<style scoped>
.metas-page { display: flex; flex-direction: column; gap: 1rem; }
.page-header { display: flex; justify-content: space-between; align-items: start; gap: 1rem; }
.page-title { margin: 0; font-size: 1.4rem; font-weight: 700; color: var(--lua-text); }
.page-subtitle { margin: 0.2rem 0 0; color: var(--lua-text-muted); font-size: 0.85rem; }

.year-tools {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.year-tools input {
    width: 110px;
}

.meta-mini-form {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 0.5rem;
}

.modal-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 220px;
    gap: 0.75rem;
}

.modal-table {
    max-height: 430px;
}

.row-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}

.feedback {
    color: var(--lua-text-muted);
    font-size: 0.86rem;
}

@media (max-width: 1100px) {
    .modal-grid,
    .row-split,
    .meta-mini-form {
        grid-template-columns: 1fr;
    }
}
</style>