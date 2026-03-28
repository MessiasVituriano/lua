<template>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div></div>
        <router-link :to="{ name: 'fornecedores.create' }" class="btn btn-lua">
            <i class="bi bi-plus-lg"></i> Novo Fornecedor
        </router-link>
    </div>

    <!-- Filtros -->
    <div class="card p-3 mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Busca</label>
                <input type="text" class="form-control form-control-sm" v-model="filters.busca" placeholder="Nome do fornecedor..." @keyup.enter="load">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Categoria</label>
                <select class="form-select form-select-sm" v-model="filters.categoria">
                    <option value="">Todas</option>
                    <option v-for="(label, key) in categorias" :key="key" :value="key">{{ label }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Status</label>
                <select class="form-select form-select-sm" v-model="filters.ativo">
                    <option value="">Todos</option>
                    <option value="1">Ativo</option>
                    <option value="0">Inativo</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-sm btn-lua" @click="load">
                    <i class="bi bi-search"></i> Filtrar
                </button>
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
                        <th>Telefone</th>
                        <th>Status</th>
                        <th width="200">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="f in fornecedores" :key="f.id">
                        <td class="fw-semibold">{{ f.nome }}</td>
                        <td><span class="badge bg-secondary">{{ categorias[f.categoria] || f.categoria }}</span></td>
                        <td>{{ f.telefone || '-' }}</td>
                        <td>
                            <span class="badge" :class="f.ativo ? 'badge-ativo' : 'badge-inativo'">
                                {{ f.ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td>
                            <router-link :to="{ name: 'fornecedores.show', params: { id: f.id } }" class="btn btn-sm btn-outline-info me-1">
                                <i class="bi bi-eye"></i>
                            </router-link>
                            <router-link :to="{ name: 'fornecedores.edit', params: { id: f.id } }" class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-pencil"></i>
                            </router-link>
                            <button class="btn btn-sm btn-outline-danger" @click="destroy(f)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="fornecedores.length === 0">
                        <td colspan="5" class="text-center text-muted py-4">Nenhum fornecedor encontrado.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { swalSuccess, swalError, swalConfirmDanger } from '../../utils/swal';
const fornecedores = ref([]);
const categorias = { racao: 'Ração', medicamento: 'Medicamento', acessorio: 'Acessório', higiene: 'Higiene', outros: 'Outros' };
const filters = reactive({ busca: '', categoria: '', ativo: '' });

async function load() {
    const params = {};
    if (filters.busca) params.busca = filters.busca;
    if (filters.categoria) params.categoria = filters.categoria;
    if (filters.ativo !== '') params.ativo = filters.ativo;
    const { data } = await axios.get('/fornecedores', { params });
    fornecedores.value = data.data;
}

function clearFilters() {
    filters.busca = '';
    filters.categoria = '';
    filters.ativo = '';
    load();
}

async function destroy(f) {
    if (!(await swalConfirmDanger('Remover fornecedor?', 'Tem certeza que deseja remover este fornecedor?'))) return;
    try {
        await axios.delete('/fornecedores/' + f.id);
        swalSuccess('Fornecedor removido com sucesso.');
        load();
    } catch {
        swalError('Erro ao remover fornecedor.');
    }
}

onMounted(load);
</script>
