<template>
    <div>
        <!-- Filtros -->
        <div class="card p-3 mb-4">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">De</label>
                    <input type="date" class="form-control form-control-sm" v-model="filters.data_inicio" @change="load">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Até</label>
                    <input type="date" class="form-control form-control-sm" v-model="filters.data_fim" @change="load">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Tipo</label>
                    <select class="form-select form-select-sm" v-model="filters.tipo" @change="load">
                        <option value="">Todos</option>
                        <option value="fixo">Fixo</option>
                        <option value="variavel">Variável</option>
                        <option value="recorrente">Recorrente</option>
                        <option value="insumo">Insumo</option>
                        <option value="comissao">Comissão</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-sm btn-lua" @click="load"><i class="bi bi-search"></i></button>
                    <button class="btn btn-sm btn-outline-secondary" @click="clearFilters">Limpar</button>
                    <button class="btn btn-sm btn-lua ms-auto" @click="openModal">
                        <i class="bi bi-plus-lg"></i> Novo Custo
                    </button>
                </div>
            </div>
        </div>

        <!-- Cards de resumo -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card p-3 text-center">
                    <div class="fs-5 fw-bold text-danger">{{ fmtMoney(totais.custos) }}</div>
                    <div class="small text-muted">Total de custos</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 text-center">
                    <div class="fs-5 fw-bold text-success">{{ fmtMoney(totais.faturamento) }}</div>
                    <div class="small text-muted">Faturamento (atend.)</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 text-center">
                    <div class="fs-5 fw-bold" :class="totais.margem >= 0 ? 'text-success' : 'text-danger'">
                        {{ totais.margem.toFixed(1) }}%
                    </div>
                    <div class="small text-muted">Margem estimada</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 text-center">
                    <div class="fs-5 fw-bold">{{ fmtMoney(totais.ticket_medio) }}</div>
                    <div class="small text-muted">Ticket médio</div>
                </div>
            </div>
        </div>

        <!-- Lista de custos -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Tipo</th>
                            <th>Serviço</th>
                            <th>Valor</th>
                            <th>Origem</th>
                            <th width="80">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in custos" :key="c.id">
                            <td class="text-nowrap">{{ fmtDate(c.data_custo) }}</td>
                            <td>{{ c.descricao }}</td>
                            <td><span class="badge bg-secondary">{{ tipoLabel(c.tipo) }}</span></td>
                            <td>{{ c.servico?.nome || '-' }}</td>
                            <td class="text-danger fw-semibold">{{ fmtMoney(c.valor) }}</td>
                            <td><span class="badge bg-light text-dark border">{{ origemLabel(c.origem) }}</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger" @click="destroy(c)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!loading && custos.length === 0">
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-graph-down fs-3 d-block mb-2 opacity-50"></i>
                                Nenhum custo registrado no período.
                            </td>
                        </tr>
                        <tr v-if="loading">
                            <td colspan="7" class="text-center py-4 text-muted">Carregando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal novo custo -->
        <div class="modal fade" ref="modalEl" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title">Registrar Custo</h6>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="modalFeedback" class="alert alert-danger small py-2">{{ modalFeedback }}</div>

                        <div class="mb-3">
                            <label class="form-label small">Descrição <span class="text-danger">*</span></label>
                            <input class="form-control form-control-sm" v-model="draft.descricao" placeholder="Ex.: Shampoo, comissão, insumos...">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small">Tipo <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" v-model="draft.tipo">
                                    <option value="">Selecione...</option>
                                    <option value="fixo">Fixo</option>
                                    <option value="variavel">Variável</option>
                                    <option value="recorrente">Recorrente</option>
                                    <option value="insumo">Insumo</option>
                                    <option value="comissao">Comissão</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Valor (R$) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" v-model="draft.valor" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Data <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" v-model="draft.data_custo">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Observação</label>
                            <textarea class="form-control form-control-sm" v-model="draft.observacao" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-sm btn-lua" :disabled="saving" @click="saveCusto">
                            {{ saving ? 'Salvando...' : 'Salvar' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { swalConfirmDanger, swalError, swalSuccess } from '../../utils/swal';

const custos = ref([]);
const loading = ref(false);
const saving = ref(false);
const modalFeedback = ref('');
const modalEl = ref(null);
let modalInstance = null;

const now = new Date();
const filters = reactive({
    data_inicio: new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10),
    data_fim: new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10),
    tipo: '',
});

const totais = reactive({ custos: 0, faturamento: 0, margem: 0, ticket_medio: 0 });

const draft = reactive({
    descricao: '', tipo: '', valor: '', data_custo: new Date().toISOString().slice(0, 10), observacao: '',
});

async function load() {
    loading.value = true;
    try {
        const params = { data_inicio: filters.data_inicio, data_fim: filters.data_fim };
        if (filters.tipo) params.tipo = filters.tipo;
        const { data } = await axios.get('/banho-tosa/custos', { params });
        custos.value = data.data || data || [];
        if (data.totais) Object.assign(totais, data.totais);
    } catch {
        custos.value = [];
    } finally {
        loading.value = false;
    }
}

function clearFilters() {
    filters.tipo = '';
    load();
}

function openModal() {
    modalFeedback.value = '';
    Object.assign(draft, { descricao: '', tipo: '', valor: '', data_custo: new Date().toISOString().slice(0, 10), observacao: '' });
    if (!modalInstance) modalInstance = new window.bootstrap.Modal(modalEl.value);
    modalInstance.show();
}

async function saveCusto() {
    modalFeedback.value = '';
    saving.value = true;
    try {
        await axios.post('/banho-tosa/custos', {
            descricao: draft.descricao,
            tipo: draft.tipo,
            valor: Number(draft.valor),
            data_custo: draft.data_custo,
            observacao: draft.observacao || null,
        });
        swalSuccess('Custo registrado.');
        modalInstance.hide();
        load();
    } catch (e) {
        modalFeedback.value = e?.response?.data?.message || 'Erro ao salvar.';
    } finally {
        saving.value = false;
    }
}

async function destroy(c) {
    if (!(await swalConfirmDanger('Remover custo?', `"${c.descricao}" será removido.`))) return;
    try {
        await axios.delete(`/banho-tosa/custos/${c.id}`);
        swalSuccess('Custo removido.');
        load();
    } catch { swalError('Erro ao remover.'); }
}

function fmtMoney(v) {
    return Number(v || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function fmtDate(d) {
    if (!d) return '-';
    const [y, m, day] = d.split('-');
    return `${day}/${m}/${y}`;
}

function tipoLabel(v) {
    return { fixo: 'Fixo', variavel: 'Variável', recorrente: 'Recorrente', insumo: 'Insumo', comissao: 'Comissão' }[v] || v;
}

function origemLabel(v) {
    return { pagamento: 'Pagamento', atendimento: 'Atendimento', manual: 'Manual' }[v] || 'Manual';
}

onMounted(load);
</script>
