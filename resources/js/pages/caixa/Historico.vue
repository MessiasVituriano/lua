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
                    <label class="form-label small">Status</label>
                    <select class="form-select form-select-sm" v-model="filters.status">
                        <option value="">Todos</option>
                        <option value="aberto">Aberto</option>
                        <option value="pendente">Pendente</option>
                        <option value="fechado">Fechado</option>
                    </select>
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
                            <th>Entradas</th>
                            <th>Saidas</th>
                            <th>Saldo</th>
                            <th>Status</th>
                            <th>Fechado por</th>
                            <th width="80">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in caixas" :key="c.id">
                            <td class="fw-semibold">{{ fmtDate(c.data) }}</td>
                            <td class="text-success">R$ {{ fmt(c.total_entradas) }}</td>
                            <td class="text-danger">R$ {{ fmt(c.total_saidas) }}</td>
                            <td :class="c.saldo >= 0 ? 'text-primary' : 'text-danger'" class="fw-bold">
                                R$ {{ fmt(c.saldo) }}
                            </td>
                            <td>
                                <span class="badge" :class="statusClass(c.status)">{{ statusLabel(c.status) }}</span>
                            </td>
                            <td>{{ c.fechado_por?.name || '-' }}</td>
                            <td class="d-flex gap-1">
                                <router-link :to="{ name: 'caixa.show', params: { id: c.id } }" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-eye"></i>
                                </router-link>
                                <button v-if="c.status === 'pendente'" class="btn btn-sm btn-outline-success" @click="autorizar(c)" title="Autorizar fechamento">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="caixas.length === 0">
                            <td colspan="7" class="text-center text-muted py-4">Nenhum registro encontrado.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { swalSuccess, swalError, swalConfirmSuccess } from '../../utils/swal';
const caixas = ref([]);
const filters = reactive({ data_inicio: '', data_fim: '', status: '' });

async function load() {
    const params = {};
    if (filters.data_inicio) params.data_inicio = filters.data_inicio;
    if (filters.data_fim) params.data_fim = filters.data_fim;
    if (filters.status) params.status = filters.status;
    const { data } = await axios.get('/caixa/historico', { params });
    caixas.value = data.data;
}

function clearFilters() {
    filters.data_inicio = '';
    filters.data_fim = '';
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

function fmt(v) { return Number(v || 0).toFixed(2).replace('.', ','); }
function fmtDate(d) { const s = typeof d === 'string' ? d.slice(0, 10) : d; return new Date(s + 'T12:00:00').toLocaleDateString('pt-BR'); }

onMounted(load);
</script>
