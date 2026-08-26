<template>
    <Modal v-model="open" :title="isEdit ? 'Editar Produto' : 'Novo Produto'" size="lg">
        <form @submit.prevent="save" id="formProdutoModal">
            <div class="row g-3">
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

                <div class="col-md-3">
                    <label class="form-label">Valor Custo *</label>
                    <input type="number" step="0.01" min="0.01" class="form-control" v-model="form.valor_custo" required @input="calcularVenda">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Margem % <span class="text-muted" style="font-size:0.75rem">(calculada)</span></label>
                    <input type="number" step="0.01" min="0" class="form-control" v-model="form.margem" required @input="calcularVenda">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Valor Venda * <span class="text-muted" style="font-size:0.75rem">(ou informe aqui)</span></label>
                    <input type="number" step="0.01" min="0" class="form-control" v-model="valorVendaCalc" @input="calcularMargem">
                </div>
                <div class="col-md-3">
                    <label class="form-label">
                        Estoque Mínimo
                        <span v-if="isRacaoForm" class="text-muted" style="font-size:0.75rem">(kg)</span>
                    </label>
                    <input v-if="isRacaoForm" type="number" step="0.001" min="0" class="form-control" v-model="estoqueMinKg" placeholder="Ex: 5 = 5 kg">
                    <input v-else type="number" min="0" class="form-control" v-model="form.estoque_min">
                </div>

                <div v-if="isRacaoForm" class="col-12">
                    <label class="form-label">
                        Peso por unidade (kg)
                        <span class="text-muted" style="font-size:0.75rem">— preencha para saco fechado; deixe vazio para venda a granel</span>
                    </label>
                    <input type="number" step="0.001" min="0.001" class="form-control" v-model="pesoUnitarioKg" placeholder="Ex: 15 = saco de 15 kg">
                    <div class="form-text">
                        Se preenchido, cada unidade vendida desconta esse peso do estoque automaticamente.
                        Deixe vazio para produtos vendidos a granel (o peso é informado na venda).
                    </div>
                </div>
            </div>

            <div v-if="isEdit" class="mt-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="produtoAtivoModal" v-model="form.ativo">
                    <label class="form-check-label" for="produtoAtivoModal">Produto ativo</label>
                </div>
            </div>
        </form>

        <template #footer>
            <button type="button" class="btn btn-outline-secondary" @click="open = false">Cancelar</button>
            <button type="submit" form="formProdutoModal" class="btn btn-lua" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-check-lg"></i> {{ isEdit ? 'Atualizar' : 'Cadastrar' }}
            </button>
        </template>
    </Modal>
</template>

<script setup>
import { reactive, ref, computed, watch } from 'vue';
import axios from 'axios';
import Modal from '../../components/Modal.vue';
import { swalSuccess, swalError } from '../../utils/swal';
import { CATEGORIAS_PRODUTO } from '../../utils/estoque';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    produtoId: { type: [Number, String, null], default: null },
    fornecedores: { type: Array, default: () => [] },
    fornecedorPadrao: { type: [Number, String, null], default: null },
});
const emit = defineEmits(['update:modelValue', 'saved']);

const open = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v),
});

const categorias = CATEGORIAS_PRODUTO;
const loading = ref(false);
const errors = reactive({});
const isEdit = computed(() => !!props.produtoId);

const form = reactive({
    nome: '', categoria: '', fornecedor_id: '',
    valor_custo: '', margem: '', estoque_min: '', ativo: true,
});
const estoqueMinKg = ref('');
const pesoUnitarioKg = ref('');
const valorVendaCalc = ref(0);
const isRacaoForm = computed(() => form.categoria === 'racao');

function reset() {
    Object.keys(errors).forEach(k => delete errors[k]);
    Object.assign(form, {
        nome: '', categoria: '', fornecedor_id: props.fornecedorPadrao || '',
        valor_custo: '', margem: '', estoque_min: '', ativo: true,
    });
    estoqueMinKg.value = '';
    pesoUnitarioKg.value = '';
    valorVendaCalc.value = 0;
}

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

async function carregar() {
    reset();
    if (!isEdit.value) return;
    const { data: prod } = await axios.get('/produtos/' + props.produtoId);
    Object.keys(form).forEach(k => { if (prod[k] !== null && prod[k] !== undefined) form[k] = prod[k]; });
    calcularVenda();
    if (prod.categoria === 'racao') {
        estoqueMinKg.value = prod.estoque_min != null ? +(prod.estoque_min / 1000).toFixed(3) : '';
        pesoUnitarioKg.value = prod.peso_unitario_gramas != null ? +(prod.peso_unitario_gramas / 1000).toFixed(3) : '';
    }
}

watch(() => props.modelValue, (v) => { if (v) carregar(); });

async function save() {
    Object.keys(errors).forEach(k => delete errors[k]);
    loading.value = true;
    try {
        const payload = { ...form };
        if (!payload.fornecedor_id) payload.fornecedor_id = null;

        if (isRacaoForm.value) {
            payload.estoque_min = estoqueMinKg.value !== '' && estoqueMinKg.value !== null
                ? Math.round(parseFloat(estoqueMinKg.value) * 1000)
                : null;
            payload.peso_unitario_gramas = pesoUnitarioKg.value !== '' && pesoUnitarioKg.value !== null && parseFloat(pesoUnitarioKg.value) > 0
                ? Math.round(parseFloat(pesoUnitarioKg.value) * 1000)
                : null;
        } else {
            if (!payload.estoque_min && payload.estoque_min !== 0) payload.estoque_min = null;
            payload.peso_unitario_gramas = null;
        }

        if (isEdit.value) {
            await axios.put('/produtos/' + props.produtoId, payload);
            swalSuccess('Produto atualizado.');
        } else {
            await axios.post('/produtos', payload);
            swalSuccess('Produto criado.');
        }
        open.value = false;
        emit('saved');
    } catch (e) {
        if (e.response?.status === 422) {
            Object.assign(errors, Object.fromEntries(
                Object.entries(e.response.data.errors).map(([k, v]) => [k, v[0]])
            ));
        } else {
            swalError('Erro ao salvar produto.');
        }
    } finally { loading.value = false; }
}
</script>
