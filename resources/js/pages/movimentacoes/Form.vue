<template>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4">
                <form @submit.prevent="save">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo *</label>
                            <select class="form-select" :class="{ 'is-invalid': errors.tipo }" v-model="form.tipo" required @change="onTipoChange">
                                <option value="">Selecione...</option>
                                <option v-for="(l, k) in tipos" :key="k" :value="k">{{ l }}</option>
                            </select>
                            <div v-if="errors.tipo" class="invalid-feedback">{{ errors.tipo }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valor *</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" :class="{ 'is-invalid': errors.valor }" v-model="form.valor" required>
                            <div v-if="errors.valor" class="invalid-feedback">{{ errors.valor }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data *</label>
                            <input type="date" class="form-control" :class="{ 'is-invalid': errors.data_movimentacao }" v-model="form.data_movimentacao" required>
                            <div v-if="errors.data_movimentacao" class="invalid-feedback">{{ errors.data_movimentacao }}</div>
                        </div>
                        <div class="col-md-6" v-if="form.tipo === 'transferencia_loja'">
                            <label class="form-label">Loja Destino *</label>
                            <select class="form-select" :class="{ 'is-invalid': errors.loja_destino_id }" v-model="form.loja_destino_id">
                                <option value="">Selecione...</option>
                                <option v-for="l in lojasDestino" :key="l.id" :value="l.id">{{ l.nome }}</option>
                            </select>
                            <div v-if="errors.loja_destino_id" class="invalid-feedback">{{ errors.loja_destino_id }}</div>
                        </div>
                        <div class="col-md-6" v-if="showBancoOrigem">
                            <label class="form-label">Banco Origem</label>
                            <select class="form-select" v-model="form.banco_origem_id">
                                <option value="">Nenhum (dinheiro fisico)</option>
                                <option v-for="b in bancos" :key="b.id" :value="b.id">{{ b.nome }}</option>
                            </select>
                        </div>
                        <div class="col-md-6" v-if="showBancoDestino">
                            <label class="form-label">Banco Destino</label>
                            <select class="form-select" v-model="form.banco_destino_id">
                                <option value="">Nenhum</option>
                                <option v-for="b in bancos" :key="b.id" :value="b.id">{{ b.nome }}</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descricao *</label>
                            <input type="text" class="form-control" :class="{ 'is-invalid': errors.descricao }" v-model="form.descricao" required>
                            <div v-if="errors.descricao" class="invalid-feedback">{{ errors.descricao }}</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observacao</label>
                            <textarea class="form-control" rows="2" v-model="form.observacao"></textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-lua" :disabled="loading">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-check-lg"></i> {{ isEdit ? 'Atualizar' : 'Cadastrar' }}
                        </button>
                        <router-link :to="{ name: 'movimentacoes.index' }" class="btn btn-outline-secondary">Cancelar</router-link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import axios from 'axios';
import { swalSuccess } from '../../utils/swal';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const loading = ref(false);
const errors = reactive({});
const isEdit = computed(() => !!route.params.id);
const bancos = ref([]);
const lojas = ref([]);

const tipos = {
    transferencia_banco: 'Transferencia entre Bancos',
    sangria: 'Sangria',
    aporte: 'Aporte',
    transferencia_loja: 'Transferencia entre Lojas',
};

const form = reactive({
    tipo: '', valor: '', data_movimentacao: new Date().toISOString().slice(0, 10),
    banco_origem_id: '', banco_destino_id: '', loja_destino_id: '',
    descricao: '', observacao: '',
});

const showBancoOrigem = computed(() => ['transferencia_banco', 'sangria'].includes(form.tipo));
const showBancoDestino = computed(() => ['transferencia_banco', 'aporte'].includes(form.tipo));
const lojasDestino = computed(() => lojas.value.filter(l => l.id !== auth.user?.loja_id));

function onTipoChange() {
    if (!showBancoOrigem.value) form.banco_origem_id = '';
    if (!showBancoDestino.value) form.banco_destino_id = '';
    if (form.tipo !== 'transferencia_loja') form.loja_destino_id = '';
}

onMounted(async () => {
    const [bancosRes, lojasRes] = await Promise.all([
        axios.get('/bancos'),
        axios.get('/lojas').catch(() => ({ data: { data: [] } })),
    ]);
    bancos.value = bancosRes.data.filter(b => b.ativo);
    lojas.value = lojasRes.data.data || lojasRes.data || [];

    if (isEdit.value) {
        const { data: mov } = await axios.get('/movimentacoes-internas/' + route.params.id);
        Object.keys(form).forEach(k => { if (mov[k] !== null && mov[k] !== undefined) form[k] = mov[k]; });
        if (mov.data_movimentacao) form.data_movimentacao = mov.data_movimentacao.slice(0, 10);
    }
});

async function save() {
    Object.keys(errors).forEach(k => delete errors[k]);
    loading.value = true;
    try {
        const payload = { ...form };
        if (!payload.banco_origem_id) payload.banco_origem_id = null;
        if (!payload.banco_destino_id) payload.banco_destino_id = null;
        if (!payload.loja_destino_id) payload.loja_destino_id = null;

        if (isEdit.value) {
            await axios.put('/movimentacoes-internas/' + route.params.id, payload);
            swalSuccess('Movimentacao atualizada.');
        } else {
            await axios.post('/movimentacoes-internas', payload);
            swalSuccess('Movimentacao registrada.');
        }
        router.push({ name: 'movimentacoes.index' });
    } catch (e) {
        if (e.response?.status === 422 && e.response.data.errors) {
            Object.assign(errors, Object.fromEntries(
                Object.entries(e.response.data.errors).map(([k, v]) => [k, v[0]])
            ));
        }
    } finally { loading.value = false; }
}
</script>
