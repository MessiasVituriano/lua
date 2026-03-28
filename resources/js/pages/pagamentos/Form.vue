<template>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4">
                <form @submit.prevent="save">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Descricao *</label>
                            <input type="text" class="form-control" :class="{ 'is-invalid': errors.descricao }" v-model="form.descricao" required>
                            <div v-if="errors.descricao" class="invalid-feedback">{{ errors.descricao }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Categoria *</label>
                            <select class="form-select" v-model="form.categoria" required>
                                <option value="">Selecione...</option>
                                <option v-for="(l, k) in categorias" :key="k" :value="k">{{ l }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Valor Total *</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" v-model="form.valor_total" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data Vencimento *</label>
                            <input type="date" class="form-control" v-model="form.data_vencimento" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fornecedor</label>
                            <select class="form-select" v-model="form.fornecedor_id">
                                <option value="">Nenhum</option>
                                <option v-for="f in fornecedores" :key="f.id" :value="f.id">{{ f.nome }}</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observacao</label>
                            <textarea class="form-control" rows="2" v-model="form.observacao"></textarea>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="recorrente" v-model="form.recorrente">
                                <label class="form-check-label" for="recorrente">Pagamento recorrente</label>
                            </div>
                        </div>
                        <div class="col-md-4" v-if="form.recorrente">
                            <label class="form-label">Dia do mes (recorrencia) *</label>
                            <input type="number" min="1" max="31" class="form-control" v-model="form.dia_recorrencia">
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-lua" :disabled="loading">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-check-lg"></i> {{ isEdit ? 'Atualizar' : 'Cadastrar' }}
                        </button>
                        <router-link :to="{ name: 'pagamentos.index' }" class="btn btn-outline-secondary">Cancelar</router-link>
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
const categorias = { boleto: 'Boleto', imposto: 'Imposto', custo_fixo: 'Custo Fixo', funcionario: 'Funcionário', fornecedor: 'Fornecedor', outros: 'Outros' };

const form = reactive({
    descricao: '', categoria: '', valor_total: '', data_vencimento: '',
    fornecedor_id: '', observacao: '', recorrente: false, dia_recorrencia: '',
});

onMounted(async () => {
    const { data } = await axios.get('/fornecedores?ativo=1');
    fornecedores.value = data.data;

    if (isEdit.value) {
        const { data: pg } = await axios.get('/pagamentos/' + route.params.id);
        Object.keys(form).forEach(k => { if (pg[k] !== null && pg[k] !== undefined) form[k] = pg[k]; });
        if (pg.data_vencimento) form.data_vencimento = pg.data_vencimento.slice(0, 10);
    }
});

async function save() {
    Object.keys(errors).forEach(k => delete errors[k]);
    loading.value = true;
    try {
        const payload = { ...form };
        if (!payload.fornecedor_id) payload.fornecedor_id = null;
        if (!payload.recorrente) payload.dia_recorrencia = null;

        if (isEdit.value) {
            await axios.put('/pagamentos/' + route.params.id, payload);
            swalSuccess('Pagamento atualizado.');
        } else {
            await axios.post('/pagamentos', payload);
            swalSuccess('Pagamento criado.');
        }
        router.push({ name: 'pagamentos.index' });
    } catch (e) {
        if (e.response?.status === 422) {
            Object.assign(errors, Object.fromEntries(
                Object.entries(e.response.data.errors).map(([k, v]) => [k, v[0]])
            ));
        }
    } finally { loading.value = false; }
}
</script>
