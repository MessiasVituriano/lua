<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div></div>
            <router-link :to="{ name: 'produtos.create' }" class="btn btn-lua">
                <i class="bi bi-plus-lg"></i> Novo Produto
            </router-link>
        </div>

        <!-- Filtros -->
        <div class="card p-3 mb-4">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Busca</label>
                    <input type="text" class="form-control form-control-sm" v-model="filters.busca" placeholder="Nome..." @keyup.enter="load">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Categoria</label>
                    <select class="form-select form-select-sm" v-model="filters.categoria">
                        <option value="">Todas</option>
                        <option v-for="(l, k) in categorias" :key="k" :value="k">{{ l }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Fornecedor</label>
                    <select class="form-select form-select-sm" v-model="filters.fornecedor_id">
                        <option value="">Todos</option>
                        <option v-for="f in fornecedores" :key="f.id" :value="f.id">{{ f.nome }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="estBaixo" v-model="filters.estoque_baixo">
                        <label class="form-check-label small" for="estBaixo">Estoque baixo</label>
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-2">
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
                            <th>Fornecedor</th>
                            <th>Custo</th>
                            <th>Margem</th>
                            <th>Venda</th>
                            <th>Estoque</th>
                            <th width="200">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in produtos" :key="p.id" :class="estoqueClass(p)">
                            <td class="fw-semibold">{{ p.nome }}</td>
                            <td><span class="badge bg-secondary">{{ categorias[p.categoria] }}</span></td>
                            <td>{{ p.fornecedor?.nome || '-' }}</td>
                            <td>R$ {{ fmt(p.valor_custo) }}</td>
                            <td>{{ p.margem }}%</td>
                            <td class="fw-bold">R$ {{ fmt(p.valor_venda) }}</td>
                            <td>
                                {{ p.estoque_atual }}
                                <span v-if="p.estoque_min !== null && p.estoque_atual <= p.estoque_min" class="text-danger">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                </span>
                            </td>
                            <td>
                                <router-link :to="{ name: 'produtos.show', params: { id: p.id } }" class="btn btn-sm btn-outline-info me-1">
                                    <i class="bi bi-eye"></i>
                                </router-link>
                                <router-link :to="{ name: 'produtos.edit', params: { id: p.id } }" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-pencil"></i>
                                </router-link>
                                <button class="btn btn-sm btn-outline-danger" @click="destroy(p)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="produtos.length === 0">
                            <td colspan="8" class="text-center text-muted py-4">Nenhum produto encontrado.</td>
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
import { swalSuccess, swalConfirmDanger } from '../../utils/swal';
const produtos = ref([]);
const fornecedores = ref([]);
const categorias = { racao: 'Ração', medicamento: 'Medicamento', acessorio: 'Acessório', higiene: 'Higiene' };
const filters = reactive({ busca: '', categoria: '', fornecedor_id: '', estoque_baixo: false });

async function load() {
    const params = {};
    if (filters.busca) params.busca = filters.busca;
    if (filters.categoria) params.categoria = filters.categoria;
    if (filters.fornecedor_id) params.fornecedor_id = filters.fornecedor_id;
    if (filters.estoque_baixo) params.estoque_baixo = '1';
    const { data } = await axios.get('/produtos', { params });
    produtos.value = data.data;
}

function clearFilters() {
    Object.keys(filters).forEach(k => filters[k] = k === 'estoque_baixo' ? false : '');
    load();
}

function estoqueClass(p) {
    if (p.estoque_min !== null && p.estoque_atual <= p.estoque_min) return 'table-danger';
    return '';
}

async function destroy(p) {
    if (!(await swalConfirmDanger('Remover produto?', 'Deseja remover este produto?'))) return;
    await axios.delete('/produtos/' + p.id);
    swalSuccess('Produto removido.');
    load();
}

function fmt(v) { return Number(v || 0).toFixed(2).replace('.', ','); }

onMounted(async () => {
    const { data } = await axios.get('/fornecedores?ativo=1');
    fornecedores.value = data.data;
    load();
});
</script>
