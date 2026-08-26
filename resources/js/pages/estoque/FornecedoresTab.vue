<template>
    <div>
        <!-- Filtros -->
        <div class="card p-3 mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Fornecedor</label>
                    <input type="text" class="form-control form-control-sm" v-model="filters.busca" placeholder="Nome do fornecedor...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">
                        Produto
                        <i class="bi bi-info-circle text-muted" title="Filtra os fornecedores que fornecem este produto e já abre a lista"></i>
                    </label>
                    <input type="text" class="form-control form-control-sm" v-model="filters.busca_produto" placeholder="Buscar por produto...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Categoria</label>
                    <select class="form-select form-select-sm" v-model="filters.categoria">
                        <option value="">Todas</option>
                        <option v-for="(l, k) in categoriasFornecedor" :key="k" :value="k">{{ l }}</option>
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
                        <input class="form-check-input" type="checkbox" id="estBaixoForn" v-model="filters.estoque_baixo">
                        <label class="form-check-label small" for="estBaixoForn">Estoque baixo</label>
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
                    {{ meta.total }} fornecedor{{ meta.total === 1 ? '' : 'es' }}
                    <span v-if="loading" class="spinner-border spinner-border-sm ms-2 text-muted"></span>
                </h6>
                <button v-if="isAdmin" class="btn btn-sm btn-lua" @click="emit('novo-fornecedor')">
                    <i class="bi bi-plus-lg"></i> Novo Fornecedor
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th width="40"></th>
                            <th>Fornecedor</th>
                            <th>Categoria</th>
                            <th>Telefone</th>
                            <th>Produtos</th>
                            <th>Status</th>
                            <th width="160">Ações</th>
                        </tr>
                    </thead>
                    <tbody v-for="f in fornecedores" :key="f.id" class="border-top-0">
                        <tr @click="toggle(f.id)" style="cursor: pointer">
                            <td>
                                <i class="bi" :class="expandidos[f.id] ? 'bi-chevron-down' : 'bi-chevron-right'"></i>
                            </td>
                            <td class="fw-semibold">{{ f.nome }}</td>
                            <td><span class="badge bg-secondary">{{ categoriasFornecedor[f.categoria] || f.categoria }}</span></td>
                            <td>{{ f.telefone || '-' }}</td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ f.produtos_count }}</span>
                            </td>
                            <td>
                                <span class="badge" :class="f.ativo ? 'badge-ativo' : 'badge-inativo'">
                                    {{ f.ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="text-nowrap" @click.stop>
                                <button v-if="isAdmin" class="btn btn-sm btn-outline-primary me-1" @click="emit('editar-fornecedor', f.id)" title="Editar fornecedor">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button v-if="isAdmin" class="btn btn-sm btn-outline-success me-1" @click="emit('novo-produto', f.id)" title="Novo produto para este fornecedor">
                                    <i class="bi bi-box-seam"></i><i class="bi bi-plus"></i>
                                </button>
                                <button v-if="isAdmin" class="btn btn-sm btn-outline-danger" @click="destroyFornecedor(f)" title="Remover fornecedor">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="expandidos[f.id]">
                            <td colspan="7" class="p-0">
                                <ProdutosDoFornecedor
                                    :estado="expandidos[f.id]"
                                    :is-admin="isAdmin"
                                    :recebido="recebido"
                                    @detalhe="id => emit('detalhe', id)"
                                    @editar="id => emit('editar', id)"
                                    @remover="removerProduto"
                                    @registrar="pedirConfirmacao" />
                            </td>
                        </tr>
                    </tbody>

                    <!-- Produtos orfaos: sem essa linha eles ficariam invisiveis nesta perspectiva -->
                    <tbody v-if="semFornecedorVisivel">
                        <tr @click="toggleSemFornecedor" style="cursor: pointer">
                            <td><i class="bi" :class="expandidos.__sem__ ? 'bi-chevron-down' : 'bi-chevron-right'"></i></td>
                            <td class="fw-semibold fst-italic">Sem fornecedor</td>
                            <td colspan="2" class="text-muted small">Produtos ainda não vinculados</td>
                            <td><span class="badge bg-light text-dark border">{{ semFornecedor.length }}</span></td>
                            <td colspan="2"></td>
                        </tr>
                        <tr v-if="expandidos.__sem__">
                            <td colspan="7" class="p-0">
                                <ProdutosDoFornecedor
                                    :estado="expandidos.__sem__"
                                    :is-admin="isAdmin"
                                    :recebido="recebido"
                                    @detalhe="id => emit('detalhe', id)"
                                    @editar="id => emit('editar', id)"
                                    @remover="removerProduto"
                                    @registrar="pedirConfirmacao" />
                            </td>
                        </tr>
                    </tbody>

                    <tbody v-if="!loading && fornecedores.length === 0 && !semFornecedorVisivel">
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <template v-if="filters.busca_produto">
                                    Nenhum fornecedor com produto contendo "{{ filters.busca_produto }}".
                                </template>
                                <template v-else>Nenhum fornecedor encontrado.</template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                <BarraRecebimento
                    v-model:motivo="motivo"
                    :total="itens.length"
                    :salvando="salvando"
                    @registrar="pedirConfirmacao"
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

        <ConfirmarRecebimentoModal
            v-model="confirmando"
            v-model:motivo="motivo"
            :itens="itens"
            :salvando="salvando"
            @confirmar="confirmar"
            @remover="remover" />
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import ProdutosDoFornecedor from './ProdutosDoFornecedor.vue';
import BarraRecebimento from './BarraRecebimento.vue';
import ConfirmarRecebimentoModal from './ConfirmarRecebimentoModal.vue';
import { useRecebimento } from '../../composables/useRecebimento';
import { swalSuccess, swalError, swalConfirmDanger } from '../../utils/swal';
import { useAuthStore } from '../../stores/auth';
import { CATEGORIAS_FORNECEDOR, debounce } from '../../utils/estoque';

const emit = defineEmits(['detalhe', 'editar', 'novo-produto', 'novo-fornecedor', 'editar-fornecedor', 'changed']);

const auth = useAuthStore();
const isAdmin = computed(() => auth.user?.role === 'admin');
const categoriasFornecedor = CATEGORIAS_FORNECEDOR;

const fornecedores = ref([]);
const semFornecedor = ref([]);
const loading = ref(false);
const page = ref(1);
const meta = reactive({ current_page: 1, last_page: 1, total: 0 });
const expandidos = reactive({});
const filters = reactive({ busca: '', busca_produto: '', categoria: '', ativo: '', estoque_baixo: false });

// Produtos de todos os fornecedores abertos — o recebimento vale para a aba inteira.
const produtosExpandidos = computed(() =>
    Object.values(expandidos).flatMap(e => e.produtos)
);
const { recebido, motivo, salvando, confirmando, itens, limpar, remover, pedirConfirmacao, confirmar } = useRecebimento(
    () => produtosExpandidos.value,
    () => emit('changed')
);

// A linha "Sem fornecedor" so faz sentido quando nao ha filtro proprio de fornecedor.
const semFornecedorVisivel = computed(() =>
    !filters.busca && !filters.categoria && filters.ativo === '' && semFornecedor.value.length > 0
);

function paramsProduto() {
    const params = {};
    if (filters.busca_produto) params.busca = filters.busca_produto;
    if (filters.estoque_baixo) params.estoque_baixo = '1';
    return params;
}

async function carregarProdutos(fornecedorId) {
    // Mantem o que ja estava na tela durante o refresh — spinner so na primeira abertura.
    const anteriores = expandidos[fornecedorId]?.produtos ?? [];
    expandidos[fornecedorId] = { loading: anteriores.length === 0, produtos: anteriores };
    const { data } = await axios.get(`/fornecedores/${fornecedorId}/produtos`, { params: paramsProduto() });
    expandidos[fornecedorId] = { loading: false, produtos: data.data };
}

async function load({ manterExpandidos = false } = {}) {
    loading.value = true;
    const abertosAntes = Object.keys(expandidos);
    try {
        const params = { page: page.value };
        if (filters.busca) params.busca = filters.busca;
        if (filters.busca_produto) params.busca_produto = filters.busca_produto;
        if (filters.categoria) params.categoria = filters.categoria;
        if (filters.ativo !== '') params.ativo = filters.ativo;
        if (filters.estoque_baixo) params.estoque_baixo = '1';

        const [{ data }] = await Promise.all([
            axios.get('/fornecedores', { params }),
            carregarSemFornecedor(),
        ]);
        fornecedores.value = data.data;
        meta.current_page = data.current_page;
        meta.last_page = data.last_page;
        meta.total = data.total;

        const idsVisiveis = new Set(fornecedores.value.map(f => String(f.id)));
        const semFornecedorAberto = abertosAntes.includes('__sem__');

        // Busca por produto ja abre o que deu match — o usuario quer ver os itens, nao clicar linha a linha.
        const reabrir = filters.busca_produto
            ? fornecedores.value.map(f => String(f.id))
            : (manterExpandidos ? abertosAntes.filter(k => k !== '__sem__' && idsVisiveis.has(k)) : []);

        const manter = new Set(reabrir);
        Object.keys(expandidos).forEach(k => { if (!manter.has(k)) delete expandidos[k]; });
        await Promise.all(reabrir.map(id => carregarProdutos(id)));

        // "Sem fornecedor" ja veio em carregarSemFornecedor() — reaproveita em vez de repetir a chamada.
        if (semFornecedorAberto && semFornecedorVisivel.value && (filters.busca_produto || manterExpandidos)) {
            expandidos.__sem__ = { loading: false, produtos: semFornecedor.value.slice() };
        }
    } finally {
        loading.value = false;
    }
}

async function carregarSemFornecedor() {
    if (filters.busca || filters.categoria || filters.ativo !== '') {
        semFornecedor.value = [];
        return;
    }
    const { data } = await axios.get('/fornecedores-sem-vinculo/produtos', { params: paramsProduto() });
    semFornecedor.value = data.data;
}

async function toggle(id) {
    if (expandidos[id]) { delete expandidos[id]; return; }
    await carregarProdutos(id);
}

function toggleSemFornecedor() {
    if (expandidos.__sem__) { delete expandidos.__sem__; return; }
    expandidos.__sem__ = { loading: false, produtos: semFornecedor.value.slice() };
}

const loadDebounced = debounce(() => { page.value = 1; load(); });

watch(() => [filters.busca, filters.busca_produto], loadDebounced);
watch(() => [filters.categoria, filters.ativo, filters.estoque_baixo], () => {
    page.value = 1;
    load();
});

function irPara(p) {
    page.value = p;
    load();
}

function clearFilters() {
    Object.assign(filters, { busca: '', busca_produto: '', categoria: '', ativo: '', estoque_baixo: false });
}

async function destroyFornecedor(f) {
    if (!(await swalConfirmDanger('Remover fornecedor?', `Deseja remover "${f.nome}"?`))) return;
    try {
        await axios.delete('/fornecedores/' + f.id);
        swalSuccess('Fornecedor removido com sucesso.');
        emit('changed');
    } catch {
        swalError('Erro ao remover fornecedor.');
    }
}

async function removerProduto(p) {
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
defineExpose({ load: () => load({ manterExpandidos: true }) });
</script>
