<template>
    <Modal v-model="open" :title="produto ? produto.nome : 'Produto'" size="xl" scrollable>
        <div v-if="produto" class="row g-3">
            <!-- Dados + movimentacao -->
            <div class="col-lg-5">
                <div class="card p-3 mb-3">
                    <h6 class="text-muted mb-3">Dados do Produto</h6>
                    <div class="mb-2">
                        <span class="text-muted small">Categoria</span>
                        <div><span class="badge bg-secondary">{{ categorias[produto.categoria] }}</span></div>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted small">Fornecedor</span>
                        <div>{{ produto.fornecedor?.nome || '-' }}</div>
                    </div>
                    <div v-if="isAdmin" class="row mb-2">
                        <div class="col-4">
                            <span class="text-muted small">Custo</span>
                            <div>R$ {{ fmtMoeda(produto.valor_custo) }}</div>
                        </div>
                        <div class="col-4">
                            <span class="text-muted small">Margem</span>
                            <div>{{ produto.margem }}%</div>
                        </div>
                        <div class="col-4">
                            <span class="text-muted small">Venda</span>
                            <div class="fw-bold text-success">R$ {{ fmtMoeda(produto.valor_venda) }}</div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted small">Estoque</span>
                        <div class="fs-4 fw-bold" :class="estoqueBaixo(produto) ? 'text-danger' : ''">
                            {{ fmtQtd(produto, produto.estoque_atual) }}
                            <small v-if="produto.estoque_min !== null" class="text-muted fs-6">
                                (min: {{ fmtQtd(produto, produto.estoque_min) }})
                            </small>
                        </div>
                    </div>
                    <div v-if="racao && produto.peso_unitario_gramas" class="mb-2">
                        <span class="text-muted small">Peso por unidade</span>
                        <div class="fw-semibold">{{ fmtGramas(produto.peso_unitario_gramas) }} / unidade</div>
                    </div>
                    <button v-if="isAdmin" class="btn btn-sm btn-outline-primary align-self-start mt-2" @click="emit('editar', produto.id)">
                        <i class="bi bi-pencil"></i> Editar
                    </button>
                </div>

                <div class="card p-3">
                    <h6 class="mb-3">Registrar Movimentação</h6>
                    <div class="mb-2">
                        <select class="form-select form-select-sm" v-model="movForm.tipo">
                            <option value="entrada">Entrada</option>
                            <option value="saida">Saída</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <template v-if="racao">
                            <input type="number" step="0.001" min="0.001" class="form-control form-control-sm" v-model="movKg" placeholder="Quantidade em kg (ex: 15 = 15 kg)">
                            <div class="form-text">Informe em kg. Ex: 15 = 15&nbsp;kg = 15.000&nbsp;g</div>
                        </template>
                        <input v-else type="number" min="1" class="form-control form-control-sm" v-model="movForm.quantidade" placeholder="Quantidade">
                    </div>
                    <div class="mb-2">
                        <input type="text" class="form-control form-control-sm" v-model="movForm.motivo" placeholder="Motivo (opcional)">
                    </div>
                    <button class="btn btn-lua btn-sm w-100" @click="registrarMov" :disabled="movLoading">
                        <span v-if="movLoading" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="bi bi-plus-lg"></i> Registrar
                    </button>
                </div>
            </div>

            <!-- Historico -->
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Histórico de Movimentações</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th>Qtd</th>
                                    <th>Motivo</th>
                                    <th>Usuário</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="m in movimentacoes" :key="m.id">
                                    <td>{{ fmtDataHora(m.created_at) }}</td>
                                    <td>
                                        <span class="badge" :class="m.tipo === 'entrada' ? 'bg-success' : 'bg-danger'">
                                            {{ m.tipo === 'entrada' ? 'Entrada' : 'Saída' }}
                                        </span>
                                    </td>
                                    <td class="fw-bold">{{ m.tipo === 'entrada' ? '+' : '-' }}{{ fmtQtd(produto, m.quantidade) }}</td>
                                    <td>{{ m.motivo || '-' }}</td>
                                    <td>{{ m.usuario?.name }}</td>
                                </tr>
                                <tr v-if="movimentacoes.length === 0">
                                    <td colspan="5" class="text-center text-muted py-4">Nenhuma movimentação registrada.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="text-center text-muted py-4">
            <span class="spinner-border spinner-border-sm me-2"></span> Carregando...
        </div>
    </Modal>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import axios from 'axios';
import Modal from '../../components/Modal.vue';
import { swalSuccess, swalError } from '../../utils/swal';
import { useAuthStore } from '../../stores/auth';
import { CATEGORIAS_PRODUTO, fmtMoeda, fmtGramas, fmtQtd, fmtDataHora, estoqueBaixo, isRacao } from '../../utils/estoque';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    produtoId: { type: [Number, String, null], default: null },
});
const emit = defineEmits(['update:modelValue', 'editar', 'changed']);

const open = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v),
});

const auth = useAuthStore();
const isAdmin = computed(() => auth.user?.role === 'admin');
const categorias = CATEGORIAS_PRODUTO;

const produto = ref(null);
const movimentacoes = ref([]);
const movForm = reactive({ tipo: 'entrada', quantidade: '', motivo: '' });
const movKg = ref('');
const movLoading = ref(false);
const racao = computed(() => isRacao(produto.value));

async function loadProduto() {
    const { data } = await axios.get('/produtos/' + props.produtoId);
    produto.value = data;
}

async function loadMovs() {
    const { data } = await axios.get('/produtos/' + props.produtoId + '/movimentacoes');
    movimentacoes.value = data.data;
}

watch(() => [props.modelValue, props.produtoId], ([v]) => {
    if (!v || !props.produtoId) return;
    produto.value = null;
    movimentacoes.value = [];
    Object.assign(movForm, { tipo: 'entrada', quantidade: '', motivo: '' });
    movKg.value = '';
    Promise.all([loadProduto(), loadMovs()]);
}, { immediate: true });

async function registrarMov() {
    const quantidade = racao.value
        ? Math.round(parseFloat(movKg.value) * 1000)
        : parseInt(movForm.quantidade);
    if (!quantidade || quantidade < 1) return;
    movLoading.value = true;
    try {
        await axios.post('/produtos/' + props.produtoId + '/movimentacao', { ...movForm, quantidade });
        movForm.quantidade = '';
        movKg.value = '';
        movForm.motivo = '';
        await Promise.all([loadProduto(), loadMovs()]);
        emit('changed');
        swalSuccess('Movimentação registrada.');
    } catch (e) {
        swalError(e.response?.data?.message || 'Erro ao registrar movimentação.');
    } finally { movLoading.value = false; }
}
</script>
