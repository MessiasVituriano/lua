<template>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4">
                <form @submit.prevent="save">
                    <div class="row g-3">
                        <!-- Linha 1: Nome | Categoria | Fornecedor -->
                        <div class="col-md-5">
                            <label class="form-label">Nome *</label>
                            <input type="text" class="form-control" :class="{ 'is-invalid': errors.nome }" v-model="form.nome" required>
                            <div v-if="errors.nome" class="invalid-feedback">{{ errors.nome }}</div>
                        </div>
                        <div class="col-md-3">
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

                        <!-- Linha 2: Valor Custo | Margem | Valor Venda | Estoque Mínimo -->
                        <div class="col-md-3">
                            <label class="form-label">Valor Custo *</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" v-model="form.valor_custo" required @input="calcularVenda">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Margem %
                                <span class="text-muted" style="font-size:0.75rem">(calculada)</span>
                            </label>
                            <input type="number" step="0.01" min="0" class="form-control" v-model="form.margem" required @input="calcularVenda">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Valor Venda *
                                <span class="text-muted" style="font-size:0.75rem">(ou informe aqui)</span>
                            </label>
                            <input type="number" step="0.01" min="0" class="form-control" v-model="valorVendaCalc" @input="calcularMargem">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">
                                Estoque Mínimo
                                <span v-if="isRacaoForm" class="text-muted" style="font-size:0.75rem">(kg)</span>
                            </label>
                            <input v-if="isRacaoForm" type="number" step="0.001" min="0" class="form-control" v-model="estoque_min_kg" placeholder="Ex: 5 = 5 kg">
                            <input v-else type="number" min="0" class="form-control" v-model="form.estoque_min">
                        </div>

                        <!-- Peso por unidade: só para ração em saco fechado -->
                        <template v-if="isRacaoForm">
                            <div class="col-12">
                                <label class="form-label">
                                    Peso por unidade (kg)
                                    <span class="text-muted" style="font-size:0.75rem">— preencha para saco fechado; deixe vazio para venda a granel</span>
                                </label>
                                <input type="number" step="0.001" min="0.001" class="form-control" v-model="peso_unitario_kg" placeholder="Ex: 15 = saco de 15 kg">
                                <div class="form-text">
                                    Se preenchido, cada unidade vendida desconta esse peso do estoque automaticamente.
                                    Deixe vazio para produtos vendidos a granel (o peso é informado na venda).
                                </div>
                            </div>
                        </template>
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
const categorias = { racao: 'Ração', racao_umida: 'Ração Úmida', medicamento: 'Medicamento', acessorio: 'Acessório', higiene: 'Higiene', petisco: 'Petisco' };

const form = reactive({
    nome: '', categoria: '', fornecedor_id: '',
    valor_custo: '', margem: '', estoque_min: '', ativo: true,
});

// Campos em kg para ração (convertidos de/para gramas na API)
const estoque_min_kg = ref('');
const peso_unitario_kg = ref('');

const isRacaoForm = computed(() => form.categoria === 'racao');

const valorVendaCalc = ref(0);

function calcularVenda() {
    const custo = parseFloat(form.valor_custo) || 0;
    const margem = parseFloat(form.margem) || 0;
    valorVendaCalc.value = Math.round(custo * (1 + margem / 100) * 100) / 100;
}

function calcularMargem() {
    const custo = parseFloat(form.valor_custo) || 0;
    const venda = parseFloat(valorVendaCalc.value) || 0;
    if (custo > 0 && venda > 0) {
        form.margem = Math.round(((venda / custo) - 1) * 100 * 100) / 100;
    }
}

onMounted(async () => {
    const { data } = await axios.get('/fornecedores?ativo=1');
    fornecedores.value = data.data;

    if (isEdit.value) {
        const { data: prod } = await axios.get('/produtos/' + route.params.id);
        Object.keys(form).forEach(k => { if (prod[k] !== null && prod[k] !== undefined) form[k] = prod[k]; });
        calcularVenda();

        const ehRacao = prod.categoria === 'racao';
        if (ehRacao) {
            estoque_min_kg.value = prod.estoque_min != null ? +(prod.estoque_min / 1000).toFixed(3) : '';
            peso_unitario_kg.value = prod.peso_unitario_gramas != null ? +(prod.peso_unitario_gramas / 1000).toFixed(3) : '';
        }
    }
});

async function save() {
    Object.keys(errors).forEach(k => delete errors[k]);
    loading.value = true;
    try {
        const payload = { ...form };
        if (!payload.fornecedor_id) payload.fornecedor_id = null;

        if (isRacaoForm.value) {
            // Converte kg → gramas (inteiro)
            payload.estoque_min = estoque_min_kg.value !== '' && estoque_min_kg.value !== null
                ? Math.round(parseFloat(estoque_min_kg.value) * 1000)
                : null;
            payload.peso_unitario_gramas = peso_unitario_kg.value !== '' && peso_unitario_kg.value !== null && parseFloat(peso_unitario_kg.value) > 0
                ? Math.round(parseFloat(peso_unitario_kg.value) * 1000)
                : null;
        } else {
            if (!payload.estoque_min && payload.estoque_min !== 0) payload.estoque_min = null;
            payload.peso_unitario_gramas = null;
        }

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
