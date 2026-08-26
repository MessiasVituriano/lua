<template>
    <div>
        <!-- Filtros -->
        <div class="card p-3 mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Busca</label>
                    <input type="text" class="form-control form-control-sm" v-model="filters.busca" placeholder="Nome do produto...">
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
                        <option value="__sem__">— Sem fornecedor —</option>
                        <option v-for="f in fornecedores" :key="f.id" :value="f.id">{{ f.nome }}</option>
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
                <div class="col-md-2 d-flex align-items-center gap-2">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="estBaixoTab" v-model="filters.estoque_baixo">
                        <label class="form-check-label small" for="estBaixoTab">Estoque baixo</label>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary ms-auto" @click="clearFilters" title="Limpar filtros">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    {{ meta.total }} produto{{ meta.total === 1 ? '' : 's' }}
                    <span v-if="loading" class="spinner-border spinner-border-sm ms-2 text-muted"></span>
                </h6>
                <button v-if="isAdmin" class="btn btn-sm btn-lua" @click="emit('novo', null)">
                    <i class="bi bi-plus-lg"></i> Novo Produto
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Fornecedor</th>
                            <th v-if="isAdmin">Custo</th>
                            <th v-if="isAdmin">Margem</th>
                            <th v-if="isAdmin">Venda</th>
                            <th>Estoque</th>
                            <th width="180">Recebido</th>
                            <th width="160">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in produtos" :key="p.id" :class="estoqueBaixo(p) ? 'table-danger' : ''">
                            <td class="fw-semibold">
                                {{ p.nome }}
                                <span v-if="!p.ativo" class="badge badge-inativo ms-1">Inativo</span>
                            </td>
                            <td><span class="badge bg-secondary">{{ categorias[p.categoria] }}</span></td>
                            <td>{{ p.fornecedor?.nome || '-' }}</td>
                            <td v-if="isAdmin">R$ {{ fmtMoeda(p.valor_custo) }}</td>
                            <td v-if="isAdmin">{{ p.margem }}%</td>
                            <td v-if="isAdmin" class="fw-bold">R$ {{ fmtMoeda(p.valor_venda) }}</td>
                            <td>
                                {{ fmtQtd(p, p.estoque_atual) }}
                                <span v-if="estoqueBaixo(p)" class="text-danger" title="Estoque abaixo do mínimo">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                </span>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input
                                        type="number"
                                        class="form-control"
                                        :step="isRacao(p) ? '0.001' : '1'"
                                        min="0"
                                        v-model="recebido[p.id]"
                                        :placeholder="isRacao(p) ? '0,000' : '0'"
                                        @keyup.enter="registrar">
                                    <span class="input-group-text">{{ isRacao(p) ? 'kg' : 'un' }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-outline-info me-1" @click="emit('detalhe', p.id)" title="Detalhes e movimentações">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button v-if="isAdmin" class="btn btn-sm btn-outline-primary me-1" @click="emit('editar', p.id)" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button v-if="isAdmin" class="btn btn-sm btn-outline-danger" @click="destroy(p)" title="Remover">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!loading && produtos.length === 0">
                            <td :colspan="isAdmin ? 9 : 6" class="text-center text-muted py-4">Nenhum produto encontrado.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <BarraRecebimento
                    v-model:motivo="motivo"
                    :total="itens.length"
                    :salvando="salvando"
                    @registrar="registrar"
                    @limpar="limpar" />
            </div>

            <div v-if="meta.last_page > 1" class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-muted">Página {{ meta.current_page }} de {{ meta.last_page }}</small>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary" :disabled="meta.current_page <= 1" @click="irPara(meta.current_page - 1)">
                        <i class="bi bi-chevron-left"></i> Anterior
                    </button>
                    <button class="btn btn-outline-secondary" :disabled="meta.current_page >= meta.last_page" @click="irPara(meta.current_page + 1)">
                        Próxima <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import { swalSuccess, swalError, swalConfirmDanger } from '../../utils/swal';
import { useAuthStore } from '../../stores/auth';
import BarraRecebimento from './BarraRecebimento.vue';
import { useRecebimento } from '../../composables/useRecebimento';
import { CATEGORIAS_PRODUTO, fmtMoeda, fmtQtd, estoqueBaixo, isRacao, debounce } from '../../utils/estoque';

defineProps({
    fornecedores: { type: Array, default: () => [] },
});
const emit = defineEmits(['detalhe', 'editar', 'novo', 'changed']);

const auth = useAuthStore();
const isAdmin = computed(() => auth.user?.role === 'admin');
const categorias = CATEGORIAS_PRODUTO;

const produtos = ref([]);
const loading = ref(false);
const page = ref(1);
const meta = reactive({ current_page: 1, last_page: 1, total: 0 });
// Filtros vivem aqui e sobrevivem enquanto a tela estiver aberta — nada de recarregar.
const filters = reactive({ busca: '', categoria: '', fornecedor_id: '', ativo: '', estoque_baixo: false });

const { recebido, motivo, salvando, itens, limpar, registrar } = useRecebimento(
    () => produtos.value,
    () => emit('changed')
);

async function load() {
    loading.value = true;
    try {
        const params = { page: page.value };
        if (filters.busca) params.busca = filters.busca;
        if (filters.categoria) params.categoria = filters.categoria;
        if (filters.fornecedor_id === '__sem__') params.sem_fornecedor = '1';
        else if (filters.fornecedor_id) params.fornecedor_id = filters.fornecedor_id;
        if (filters.ativo !== '') params.ativo = filters.ativo;
        if (filters.estoque_baixo) params.estoque_baixo = '1';

        const { data } = await axios.get('/produtos', { params });
        produtos.value = data.data;
        meta.current_page = data.current_page;
        meta.last_page = data.last_page;
        meta.total = data.total;
    } finally {
        loading.value = false;
    }
}

const loadDebounced = debounce(() => { page.value = 1; load(); });

watch(() => filters.busca, loadDebounced);
watch(() => [filters.categoria, filters.fornecedor_id, filters.ativo, filters.estoque_baixo], () => {
    page.value = 1;
    load();
});

function irPara(p) {
    page.value = p;
    load();
}

function clearFilters() {
    Object.assign(filters, { busca: '', categoria: '', fornecedor_id: '', ativo: '', estoque_baixo: false });
}

async function destroy(p) {
    if (!(await swalConfirmDanger('Remover produto?', `Deseja remover "${p.nome}"?`))) return;
    try {
        await axios.delete('/produtos/' + p.id);
        swalSuccess('Produto removido.');
        emit('changed');
    } catch {
        swalError('Erro ao remover produto.');
    }
}

onMounted(load);
defineExpose({ load });
</script>
