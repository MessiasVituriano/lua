<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <!-- Alertas -->
            <div class="d-flex gap-2">
                <span v-if="alertas.total_atrasados > 0" class="badge bg-danger fs-6">
                    <i class="bi bi-exclamation-triangle"></i> {{ alertas.total_atrasados }} atrasado(s)
                </span>
                <span v-if="alertas.total_proximos > 0" class="badge bg-warning text-dark fs-6">
                    <i class="bi bi-clock"></i> {{ alertas.total_proximos }} vencendo em 7 dias
                </span>
            </div>
            <router-link :to="{ name: 'pagamentos.create' }" class="btn btn-lua">
                <i class="bi bi-plus-lg"></i> Novo Pagamento
            </router-link>
        </div>

        <!-- Filtros -->
        <div class="card p-3 mb-4">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">Status</label>
                    <select class="form-select form-select-sm" v-model="filters.status">
                        <option value="">Todos</option>
                        <option value="pendente">Pendente</option>
                        <option value="pago">Pago</option>
                        <option value="atrasado">Atrasado</option>
                        <option value="parcial">Parcial</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Categoria</label>
                    <select class="form-select form-select-sm" v-model="filters.categoria">
                        <option value="">Todas</option>
                        <option v-for="(l, k) in categorias" :key="k" :value="k">{{ l }}</option>
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
                    <button class="btn btn-sm btn-lua" @click="load"><i class="bi bi-search"></i> Filtrar</button>
                    <button class="btn btn-sm btn-outline-secondary" @click="clearFilters">Limpar</button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Descricao</th>
                            <th>Categoria</th>
                            <th>Fornecedor</th>
                            <th>Vencimento</th>
                            <th>Valor</th>
                            <th>Pago</th>
                            <th>Status</th>
                            <th width="180">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in pagamentos" :key="p.id" :class="rowClass(p)">
                            <td class="fw-semibold">
                                {{ p.descricao }}
                                <i v-if="p.recorrente" class="bi bi-arrow-repeat text-muted" title="Recorrente"></i>
                            </td>
                            <td><span class="badge bg-secondary">{{ categorias[p.categoria] }}</span></td>
                            <td>{{ p.fornecedor?.nome || '-' }}</td>
                            <td>{{ fmtDate(p.data_vencimento) }}</td>
                            <td>R$ {{ fmt(p.valor_total) }}</td>
                            <td>R$ {{ fmt(p.valor_pago) }}</td>
                            <td>
                                <span class="badge" :class="statusClass(p.status)">{{ statusLabel(p.status) }}</span>
                            </td>
                            <td>
                                <button v-if="p.status !== 'pago'" class="btn btn-sm btn-outline-success me-1" @click="abrirPagar(p)" title="Registrar Pagamento">
                                    <i class="bi bi-cash"></i>
                                </button>
                                <router-link v-if="p.status !== 'pago'" :to="{ name: 'pagamentos.edit', params: { id: p.id } }" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-pencil"></i>
                                </router-link>
                                <button class="btn btn-sm btn-outline-danger" @click="destroy(p)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="pagamentos.length === 0">
                            <td colspan="8" class="text-center text-muted py-4">Nenhum pagamento encontrado.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Pagar -->
        <div class="modal fade" id="modalPagar" tabindex="-1" ref="modalEl">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Registrar Pagamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" v-if="pagamentoSelecionado">
                        <p class="mb-1"><strong>{{ pagamentoSelecionado.descricao }}</strong></p>
                        <p class="text-muted small mb-3">
                            Total: R$ {{ fmt(pagamentoSelecionado.valor_total) }} |
                            Ja pago: R$ {{ fmt(pagamentoSelecionado.valor_pago) }} |
                            Restante: R$ {{ fmt(pagamentoSelecionado.valor_total - pagamentoSelecionado.valor_pago) }}
                        </p>
                        <div class="mb-3">
                            <label class="form-label small">Valor a pagar *</label>
                            <input type="number" step="0.01" class="form-control" v-model="pgForm.valor_pago">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Forma *</label>
                            <select class="form-select" v-model="pgForm.forma_pagamento">
                                <option value="">Selecione...</option>
                                <option value="dinheiro">Dinheiro</option>
                                <option value="pix">PIX</option>
                                <option value="boleto">Boleto</option>
                                <option value="transferencia">Transferencia</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Banco</label>
                            <select class="form-select" v-model="pgForm.banco_id">
                                <option value="">-</option>
                                <option v-for="b in bancos" :key="b.id" :value="b.id">{{ b.nome }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Data Pagamento *</label>
                            <input type="date" class="form-control" v-model="pgForm.data_pagamento">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-lua" @click="confirmarPagamento" :disabled="pgLoading">
                            <span v-if="pgLoading" class="spinner-border spinner-border-sm me-1"></span>
                            Confirmar Pagamento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { swalSuccess, swalError, swalConfirmDanger } from '../../utils/swal';
const pagamentos = ref([]);
const alertas = ref({ total_atrasados: 0, total_proximos: 0 });
const bancos = ref([]);
const categorias = { boleto: 'Boleto', imposto: 'Imposto', custo_fixo: 'Custo Fixo', funcionario: 'Funcionário', fornecedor: 'Fornecedor', outros: 'Outros' };
const filters = reactive({ status: '', categoria: '', data_inicio: '', data_fim: '' });
const pagamentoSelecionado = ref(null);
const pgForm = reactive({ valor_pago: '', forma_pagamento: '', banco_id: '', data_pagamento: new Date().toISOString().slice(0, 10) });
const pgLoading = ref(false);
const modalEl = ref(null);
let modalInstance = null;

async function load() {
    const params = {};
    Object.entries(filters).forEach(([k, v]) => { if (v) params[k] = v; });
    const [pgRes, alRes] = await Promise.all([
        axios.get('/pagamentos', { params }),
        axios.get('/pagamentos-alertas'),
    ]);
    pagamentos.value = pgRes.data.data;
    alertas.value = alRes.data;
}

function clearFilters() {
    Object.keys(filters).forEach(k => filters[k] = '');
    load();
}

function rowClass(p) {
    if (p.status === 'atrasado') return 'table-danger';
    const venc = new Date(p.data_vencimento + 'T12:00:00');
    const em7 = new Date(); em7.setDate(em7.getDate() + 7);
    if (p.status === 'pendente' && venc <= em7) return 'table-warning';
    return '';
}

function statusClass(s) {
    return { pendente: 'bg-info', pago: 'bg-success', atrasado: 'bg-danger', parcial: 'bg-warning text-dark' }[s];
}

function statusLabel(s) {
    return { pendente: 'Pendente', pago: 'Pago', atrasado: 'Atrasado', parcial: 'Parcial' }[s];
}

function abrirPagar(p) {
    pagamentoSelecionado.value = p;
    pgForm.valor_pago = (p.valor_total - p.valor_pago).toFixed(2);
    pgForm.forma_pagamento = '';
    pgForm.banco_id = '';
    pgForm.data_pagamento = new Date().toISOString().slice(0, 10);
    nextTick(() => {
        if (!modalInstance) {
            modalInstance = new window.bootstrap.Modal(modalEl.value);
        }
        modalInstance.show();
    });
}

async function confirmarPagamento() {
    pgLoading.value = true;
    try {
        await axios.post('/pagamentos/' + pagamentoSelecionado.value.id + '/pagar', pgForm);
        modalInstance.hide();
        swalSuccess('Pagamento registrado com sucesso.');
        await load();
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao registrar pagamento.');
    } finally { pgLoading.value = false; }
}

async function destroy(p) {
    if (!(await swalConfirmDanger('Remover pagamento?', 'Deseja remover este pagamento?'))) return;
    await axios.delete('/pagamentos/' + p.id);
    swalSuccess('Pagamento removido.');
    load();
}

function fmt(v) { return Number(v || 0).toFixed(2).replace('.', ','); }
function fmtDate(d) { const s = typeof d === 'string' ? d.slice(0, 10) : d; return new Date(s + 'T12:00:00').toLocaleDateString('pt-BR'); }

onMounted(async () => {
    const { data } = await axios.get('/bancos');
    bancos.value = data.filter(b => b.ativo);
    load();
});
</script>
