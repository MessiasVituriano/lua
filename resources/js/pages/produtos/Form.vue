<template>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4">
                <form @submit.prevent="save">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nome *</label>
                            <input type="text" class="form-control" :class="{ 'is-invalid': errors.nome }" v-model="form.nome" required>
                            <div v-if="errors.nome" class="invalid-feedback">{{ errors.nome }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Categoria *</label>
                            <select class="form-select" v-model="form.categoria" required>
                                <option value="">Selecione...</option>
                                <option v-for="(l, k) in categorias" :key="k" :value="k">{{ l }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fornecedor</label>
                            <select class="form-select" v-model="form.fornecedor_id">
                                <option value="">Nenhum</option>
                                <option v-for="f in fornecedores" :key="f.id" :value="f.id">{{ f.nome }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Valor Custo *</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" v-model="form.valor_custo" required @input="calcularVenda">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Margem % *</label>
                            <input type="number" step="0.01" min="0" class="form-control" v-model="form.margem" required @input="calcularVenda">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Valor Venda</label>
                            <input type="text" class="form-control bg-light" :value="'R$ ' + fmtValorVenda" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estoque Minimo</label>
                            <input type="number" min="0" class="form-control" v-model="form.estoque_min">
                        </div>
                    </div>

                    <div v-if="isEdit" class="mt-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="ativo" v-model="form.ativo">
                            <label class="form-check-label" for="ativo">Produto ativo</label>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-lua" :disabled="loading">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-check-lg"></i> {{ isEdit ? 'Atualizar' : 'Cadastrar' }}
                        </button>
                        <router-link :to="{ name: 'produtos.index' }" class="btn btn-outline-secondary">Cancelar</router-link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { swalSuccess } from '../../utils/swal';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const errors = reactive({});
const isEdit = computed(() => !!route.params.id);
const fornecedores = ref([]);
const categorias = { racao: 'Ração', medicamento: 'Medicamento', acessorio: 'Acessório', higiene: 'Higiene', petisco: 'Petisco' };

const form = reactive({
    nome: '', categoria: '', fornecedor_id: '',
    valor_custo: '', margem: '', estoque_min: '', ativo: true,
});

const valorVendaCalc = ref(0);
const fmtValorVenda = computed(() => valorVendaCalc.value.toFixed(2).replace('.', ','));

function calcularVenda() {
    const custo = parseFloat(form.valor_custo) || 0;
    const margem = parseFloat(form.margem) || 0;
    valorVendaCalc.value = Math.round(custo * (1 + margem / 100) * 100) / 100;
}

onMounted(async () => {
    const { data } = await axios.get('/fornecedores?ativo=1');
    fornecedores.value = data.data;

    if (isEdit.value) {
        const { data: prod } = await axios.get('/produtos/' + route.params.id);
        Object.keys(form).forEach(k => { if (prod[k] !== null && prod[k] !== undefined) form[k] = prod[k]; });
        calcularVenda();
    }
});

async function save() {
    Object.keys(errors).forEach(k => delete errors[k]);
    loading.value = true;
    try {
        const payload = { ...form };
        if (!payload.fornecedor_id) payload.fornecedor_id = null;
        if (!payload.estoque_min && payload.estoque_min !== 0) payload.estoque_min = null;

        if (isEdit.value) {
            await axios.put('/produtos/' + route.params.id, payload);
            swalSuccess('Produto atualizado.');
        } else {
            await axios.post('/produtos', payload);
            swalSuccess('Produto criado.');
        }
        router.push({ name: 'produtos.index' });
    } catch (e) {
        if (e.response?.status === 422) {
            Object.assign(errors, Object.fromEntries(
                Object.entries(e.response.data.errors).map(([k, v]) => [k, v[0]])
            ));
        }
    } finally { loading.value = false; }
}
</script>
