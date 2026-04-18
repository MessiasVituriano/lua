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
                                <router-link v-if="m.status === 'solicitada'" :to="{ name: 'movimentacoes.edit', params: { id: m.id } }" class="btn btn-sm btn-outline-primary me-1" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </router-link>
                                <button v-if="m.status !== 'aprovada'" class="btn btn-sm btn-outline-danger" @click="destroy(m)" title="Remover">
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
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';
import { swalSuccess, swalError, swalConfirmDanger, swalConfirmSuccess } from '../../utils/swal';

const auth = useAuthStore();
const isAdmin = computed(() => auth.user?.role === 'admin');
const movimentacoes = ref([]);
const totalPendentes = ref(0);
const tipos = { transferencia_banco: 'Transf. Banco', sangria: 'Sangria', aporte: 'Aporte', transferencia_loja: 'Transf. Loja' };
const filters = reactive({ tipo: '', status: '', data_inicio: '', data_fim: '' });

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

function clearFilters() {
    Object.keys(filters).forEach(k => filters[k] = '');
    load();
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
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao remover.');
    }
}

function fmt(v) { return Number(v || 0).toFixed(2).replace('.', ','); }
function fmtDate(d) { const s = typeof d === 'string' ? d.slice(0, 10) : d; return new Date(s + 'T12:00:00').toLocaleDateString('pt-BR'); }

onMounted(load);
</script>
