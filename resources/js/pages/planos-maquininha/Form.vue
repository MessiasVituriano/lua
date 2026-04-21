<template>
    <div class="card p-4 mb-3">
        <form @submit.prevent="save">
            <div class="row g-3 mb-4">
                <div class="col-md-5">
                    <label class="form-label">Nome do Plano *</label>
                    <input type="text" class="form-control" :class="{ 'is-invalid': errors.nome }" v-model="form.nome" placeholder="Ex: Stone Smart" required>
                    <div v-if="errors.nome" class="invalid-feedback">{{ errors.nome }}</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Taxa Antecipacao (%)</label>
                    <input type="number" step="0.01" min="0" max="100" class="form-control" v-model="form.taxa_antecipacao" placeholder="0,00">
                    <small class="text-muted">Aplicada em vendas no credito. Deixe vazio p/ nao aplicar.</small>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="ativo" v-model="form.ativo">
                        <label class="form-check-label" for="ativo">Plano ativo (usado nas vendas)</label>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Minhas taxas</h6>
                <small class="text-muted">Deixe vazio ("—") se a bandeira nao aceita a modalidade.</small>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 22%">Bandeira</th>
                            <th v-for="m in modalidades" :key="m.key" class="text-center">{{ m.label }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="b in bandeiras" :key="b.id">
                            <td>
                                <div class="fw-semibold">{{ b.nome }}</div>
                                <small v-if="!b.ativo" class="text-muted">Inativa</small>
                            </td>
                            <td v-for="m in modalidades" :key="m.key" class="text-center">
                                <div class="input-group input-group-sm" style="min-width: 110px">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        class="form-control text-end"
                                        :value="taxaValue(b.id, m.key)"
                                        @input="setTaxa(b.id, m.key, $event.target.value)"
                                        placeholder="—"
                                    >
                                    <span class="input-group-text">%</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="bandeiras.length === 0">
                            <td :colspan="modalidades.length + 1" class="text-center text-muted py-4">
                                Nenhuma bandeira cadastrada.
                                <router-link :to="{ name: 'bandeiras.create' }">Cadastrar bandeira</router-link>.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-lua" :disabled="loading">
                    <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="bi bi-check-lg"></i> {{ isEdit ? 'Atualizar' : 'Cadastrar' }}
                </button>
                <router-link :to="{ name: 'planos-maquininha.index' }" class="btn btn-outline-secondary">Cancelar</router-link>
            </div>
        </form>
    </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { swalSuccess, swalError } from '../../utils/swal';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const errors = reactive({});
const isEdit = computed(() => !!route.params.id);

const form = reactive({
    nome: '',
    taxa_antecipacao: '',
    ativo: true,
});

const modalidades = [
    { key: 'debito', label: 'Debito' },
    { key: 'credito_avista', label: 'Credito' },
    { key: 'credito_2_6', label: '2x a 6x' },
    { key: 'credito_7_12', label: '7x a 12x' },
];

const bandeiras = ref([]);
// Map bandeira_id -> { modalidade: value }
const taxasMap = reactive({});

function taxaValue(bandeiraId, modalidade) {
    const v = taxasMap[bandeiraId]?.[modalidade];
    return v === null || v === undefined ? '' : v;
}

function setTaxa(bandeiraId, modalidade, value) {
    if (!taxasMap[bandeiraId]) taxasMap[bandeiraId] = {};
    taxasMap[bandeiraId][modalidade] = value === '' ? null : parseFloat(value);
}

async function load() {
    if (isEdit.value) {
        const { data } = await axios.get('/planos-maquininha/' + route.params.id);
        form.nome = data.plano.nome;
        form.taxa_antecipacao = data.plano.taxa_antecipacao ?? '';
        form.ativo = data.plano.ativo;
        bandeiras.value = data.bandeiras;
        data.bandeiras.forEach(b => {
            taxasMap[b.id] = { ...(b.taxas || {}) };
        });
    } else {
        const { data } = await axios.get('/bandeiras');
        bandeiras.value = data.filter(b => b.ativo);
        bandeiras.value.forEach(b => {
            taxasMap[b.id] = {};
            modalidades.forEach(m => { taxasMap[b.id][m.key] = null; });
        });
    }
}

function payloadTaxas() {
    const out = [];
    bandeiras.value.forEach(b => {
        modalidades.forEach(m => {
            const v = taxasMap[b.id]?.[m.key];
            out.push({
                bandeira_id: b.id,
                modalidade: m.key,
                taxa: v === null || v === undefined || v === '' ? null : v,
            });
        });
    });
    return out;
}

async function save() {
    Object.keys(errors).forEach(k => delete errors[k]);
    loading.value = true;

    const payload = {
        nome: form.nome,
        taxa_antecipacao: form.taxa_antecipacao === '' ? null : form.taxa_antecipacao,
        ativo: form.ativo,
        taxas: payloadTaxas(),
    };

    try {
        if (isEdit.value) {
            await axios.put('/planos-maquininha/' + route.params.id, payload);
            swalSuccess('Plano atualizado com sucesso.');
        } else {
            await axios.post('/planos-maquininha', payload);
            swalSuccess('Plano criado com sucesso.');
        }
        router.push({ name: 'planos-maquininha.index' });
    } catch (e) {
        if (e.response?.status === 422) {
            Object.assign(errors, Object.fromEntries(
                Object.entries(e.response.data.errors || {}).map(([k, v]) => [k, v[0]])
            ));
            swalError(e.response.data.message || 'Verifique os campos.');
        } else {
            swalError('Erro ao salvar plano.');
        }
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>
