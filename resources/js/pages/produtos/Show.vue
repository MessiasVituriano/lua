<template>
    <div v-if="produto">
        <div class="row">
            <!-- Dados -->
            <div class="col-md-4">
                <div class="card p-4 mb-3">
                    <h6 class="text-muted mb-3">Dados do Produto</h6>
                    <div class="mb-2">
                        <span class="text-muted small">Nome</span>
                        <div class="fw-semibold">{{ produto.nome }}</div>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted small">Categoria</span>
                        <div><span class="badge bg-secondary">{{ categorias[produto.categoria] }}</span></div>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted small">Fornecedor</span>
                        <div>{{ produto.fornecedor?.nome || '-' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4">
                            <span class="text-muted small">Custo</span>
                            <div>R$ {{ fmt(produto.valor_custo) }}</div>
                        </div>
                        <div class="col-4">
                            <span class="text-muted small">Margem</span>
                            <div>{{ produto.margem }}%</div>
                        </div>
                        <div class="col-4">
                            <span class="text-muted small">Venda</span>
                            <div class="fw-bold text-success">R$ {{ fmt(produto.valor_venda) }}</div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted small">Estoque</span>
                        <div class="fs-4 fw-bold" :class="estoqueAlert ? 'text-danger' : ''">
                            {{ produto.estoque_atual }}
                            <small v-if="produto.estoque_min !== null" class="text-muted fs-6">(min: {{ produto.estoque_min }})</small>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        <router-link :to="{ name: 'produtos.edit', params: { id: produto.id } }" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Editar
                        </router-link>
                        <router-link :to="{ name: 'produtos.index' }" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </router-link>
                    </div>
                </div>

                <!-- Movimentacao -->
                <div class="card p-4">
                    <h6 class="mb-3">Registrar Movimentacao</h6>
                    <div class="mb-2">
                        <select class="form-select form-select-sm" v-model="movForm.tipo">
                            <option value="entrada">Entrada</option>
                            <option value="saida">Saida</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <input type="number" min="1" class="form-control form-control-sm" v-model="movForm.quantidade" placeholder="Quantidade">
                    </div>
                    <div class="mb-2">
                        <input type="text" class="form-control form-control-sm" v-model="movForm.motivo" placeholder="Motivo (opcional)">
                    </div>
                    <button class="btn btn-lua btn-sm w-100" @click="registrarMov" :disabled="movLoading">
                        <i class="bi bi-plus-lg"></i> Registrar
                    </button>
                </div>
            </div>

            <!-- Historico -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Historico de Movimentacoes</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th>Qtd</th>
                                    <th>Motivo</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="m in movimentacoes" :key="m.id">
                                    <td>{{ fmtDt(m.created_at) }}</td>
                                    <td>
                                        <span class="badge" :class="m.tipo === 'entrada' ? 'bg-success' : 'bg-danger'">
                                            {{ m.tipo === 'entrada' ? 'Entrada' : 'Saida' }}
                                        </span>
                                    </td>
                                    <td class="fw-bold">{{ m.tipo === 'entrada' ? '+' : '-' }}{{ m.quantidade }}</td>
                                    <td>{{ m.motivo || '-' }}</td>
                                    <td>{{ m.usuario?.name }}</td>
                                </tr>
                                <tr v-if="movimentacoes.length === 0">
                                    <td colspan="5" class="text-center text-muted py-4">Nenhuma movimentacao registrada.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { swalSuccess, swalError } from '../../utils/swal';

const route = useRoute();
const produto = ref(null);
const movimentacoes = ref([]);
const movForm = reactive({ tipo: 'entrada', quantidade: '', motivo: '' });
const movLoading = ref(false);
const categorias = { racao: 'Ração', medicamento: 'Medicamento', acessorio: 'Acessório', higiene: 'Higiene' };
const estoqueAlert = computed(() => produto.value && produto.value.estoque_min !== null && produto.value.estoque_atual <= produto.value.estoque_min);

async function loadProduto() {
    const { data } = await axios.get('/produtos/' + route.params.id);
    produto.value = data;
}

async function loadMovs() {
    const { data } = await axios.get('/produtos/' + route.params.id + '/movimentacoes');
    movimentacoes.value = data.data;
}

async function registrarMov() {
    if (!movForm.quantidade || movForm.quantidade < 1) return;
    movLoading.value = true;
    try {
        await axios.post('/produtos/' + route.params.id + '/movimentacao', movForm);
        movForm.quantidade = '';
        movForm.motivo = '';
        await Promise.all([loadProduto(), loadMovs()]);
        swalSuccess('Movimentacao registrada.');
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao registrar movimentacao.');
    } finally { movLoading.value = false; }
}

function fmt(v) { return Number(v || 0).toFixed(2).replace('.', ','); }
function fmtDt(d) { return new Date(d).toLocaleString('pt-BR'); }

onMounted(() => Promise.all([loadProduto(), loadMovs()]));
</script>
