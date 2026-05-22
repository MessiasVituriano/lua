<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div></div>
            <router-link v-if="isAdmin" :to="{ name: 'banho-tosa.servicos.create' }" class="btn btn-lua">
                <i class="bi bi-plus-lg"></i> Novo Serviço
            </router-link>
        </div>

        <div class="card p-3 mb-4">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small"><i class="bi bi-search me-1"></i>Busca</label>
                    <input type="text" class="form-control form-control-sm" v-model="filters.busca" placeholder="Nome do serviço..." @keyup.enter="load">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Categoria</label>
                    <select class="form-select form-select-sm" v-model="filters.categoria" @change="load">
                        <option value="">Todas</option>
                        <option value="banho">Banho</option>
                        <option value="tosa">Tosa</option>
                        <option value="pacote">Pacote</option>
                        <option value="extra">Extra</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Status</label>
                    <select class="form-select form-select-sm" v-model="filters.ativo" @change="load">
                        <option value="">Todos</option>
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-sm btn-lua" @click="load"><i class="bi bi-search"></i></button>
                    <button class="btn btn-sm btn-outline-secondary" @click="clearFilters">Limpar</button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Duração</th>
                            <th>Preço base</th>
                            <th>Custo est.</th>
                            <th>Margem est.</th>
                            <th>Status</th>
                            <th v-if="isAdmin" width="100">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in servicos" :key="s.id">
                            <td class="fw-semibold">{{ s.nome }}</td>
                            <td>{{ categoriaLabel(s.categoria) }}</td>
                            <td>{{ s.duracao_minutos }} min</td>
                            <td>{{ fmtMoney(s.preco_base) }}</td>
                            <td>{{ s.custo_estimado ? fmtMoney(s.custo_estimado) : '-' }}</td>
                            <td>
                                <span v-if="s.custo_estimado && s.preco_base" :class="margem(s) >= 0 ? 'text-success' : 'text-danger'">
                                    {{ margem(s).toFixed(0) }}%
                                </span>
                                <span v-else class="text-muted">-</span>
                            </td>
                            <td>
                                <span class="badge" :class="s.ativo ? 'badge-ativo' : 'badge-inativo'">
                                    {{ s.ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td v-if="isAdmin">
                                <router-link :to="{ name: 'banho-tosa.servicos.edit', params: { id: s.id } }" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-pencil"></i>
                                </router-link>
                                <button class="btn btn-sm btn-outline-danger" @click="destroy(s)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!loading && servicos.length === 0">
                            <td :colspan="isAdmin ? 8 : 7" class="text-center text-muted py-5">
                                <i class="bi bi-scissors fs-3 d-block mb-2 opacity-50"></i>
                                Nenhum serviço cadastrado.
                            </td>
                        </tr>
                        <tr v-if="loading">
                            <td :colspan="isAdmin ? 8 : 7" class="text-center py-4 text-muted">Carregando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';
import { swalConfirmDanger, swalError, swalSuccess } from '../../utils/swal';

const auth = useAuthStore();
const isAdmin = computed(() => auth.user?.role === 'admin');
const servicos = ref([]);
const loading = ref(false);
const filters = reactive({ busca: '', categoria: '', ativo: '' });

async function load() {
    loading.value = true;
    try {
        const params = {};
        if (filters.busca) params.busca = filters.busca;
        if (filters.categoria) params.categoria = filters.categoria;
        if (filters.ativo !== '') params.ativo = filters.ativo;
        const { data } = await axios.get('/banho-tosa/servicos', { params });
        servicos.value = data.data || data || [];
    } catch {
        servicos.value = [];
    } finally {
        loading.value = false;
    }
}

function clearFilters() {
    filters.busca = '';
    filters.categoria = '';
    filters.ativo = '';
    load();
}

async function destroy(s) {
    if (!(await swalConfirmDanger('Remover serviço?', `"${s.nome}" será removido.`))) return;
    try {
        await axios.delete(`/banho-tosa/servicos/${s.id}`);
        swalSuccess('Serviço removido.');
        load();
    } catch { swalError('Erro ao remover serviço.'); }
}

function categoriaLabel(v) {
    return { banho: 'Banho', tosa: 'Tosa', pacote: 'Pacote', extra: 'Extra' }[v] || v;
}

function fmtMoney(v) {
    return Number(v || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function margem(s) {
    return ((s.preco_base - s.custo_estimado) / s.preco_base) * 100;
}

onMounted(load);
</script>
