<template>
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card p-4">
                <h6 class="mb-4 fw-semibold">{{ isEdit ? 'Editar Serviço' : 'Novo Serviço' }}</h6>

                <div v-if="feedback" class="alert alert-danger small py-2">{{ feedback }}</div>

                <div class="mb-3">
                    <label class="form-label small">Nome <span class="text-danger">*</span></label>
                    <input class="form-control form-control-sm" v-model="form.nome" placeholder="Ex.: Banho simples, Tosa na tesoura..." maxlength="255">
                </div>

                <div class="mb-3">
                    <label class="form-label small">Categoria <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" v-model="form.categoria">
                        <option value="">Selecione...</option>
                        <option value="banho">Banho</option>
                        <option value="tosa">Tosa</option>
                        <option value="pacote">Pacote</option>
                        <option value="extra">Extra</option>
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small">Preço base (R$) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-sm" v-model="form.preco_base" min="0" step="0.01" placeholder="0,00">
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Custo estimado (R$)</label>
                        <input type="number" class="form-control form-control-sm" v-model="form.custo_estimado" min="0" step="0.01" placeholder="0,00">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small">Duração (minutos) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control form-control-sm" v-model="form.duracao_minutos" min="5" step="5" placeholder="60">
                </div>

                <div class="mb-3">
                    <label class="form-label small">Descrição</label>
                    <textarea class="form-control form-control-sm" v-model="form.descricao" rows="2" placeholder="Detalhamento comercial opcional..."></textarea>
                </div>

                <div class="mb-4 form-check">
                    <input class="form-check-input" type="checkbox" id="ativo" v-model="form.ativo">
                    <label class="form-check-label small" for="ativo">Serviço ativo</label>
                </div>

                <!-- Margem calculada -->
                <div v-if="form.preco_base > 0 && form.custo_estimado > 0" class="alert alert-info py-2 small mb-4">
                    Margem estimada: <strong>{{ margemCalculada }}%</strong>
                    (R$ {{ lucroCalculado }} por atendimento)
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <router-link :to="{ name: 'banho-tosa.servicos.index' }" class="btn btn-sm btn-outline-secondary">Cancelar</router-link>
                    <button class="btn btn-sm btn-lua" :disabled="saving" @click="save">
                        {{ saving ? 'Salvando...' : 'Salvar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { swalError, swalSuccess } from '../../utils/swal';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => !!route.params.id);
const saving = ref(false);
const feedback = ref('');

const form = reactive({
    nome: '',
    categoria: '',
    preco_base: '',
    custo_estimado: '',
    duracao_minutos: 60,
    descricao: '',
    ativo: true,
});

const margemCalculada = computed(() => {
    const p = Number(form.preco_base);
    const c = Number(form.custo_estimado);
    if (!p || !c) return 0;
    return (((p - c) / p) * 100).toFixed(1);
});

const lucroCalculado = computed(() => {
    const p = Number(form.preco_base);
    const c = Number(form.custo_estimado);
    return (p - c).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
});

async function load() {
    if (!isEdit.value) return;
    try {
        const { data } = await axios.get(`/banho-tosa/servicos/${route.params.id}`);
        Object.assign(form, {
            nome: data.nome,
            categoria: data.categoria,
            preco_base: data.preco_base,
            custo_estimado: data.custo_estimado ?? '',
            duracao_minutos: data.duracao_minutos,
            descricao: data.descricao ?? '',
            ativo: data.ativo,
        });
    } catch {
        swalError('Erro ao carregar serviço.');
        router.push({ name: 'banho-tosa.servicos.index' });
    }
}

async function save() {
    feedback.value = '';
    saving.value = true;
    try {
        const payload = {
            nome: form.nome,
            categoria: form.categoria,
            preco_base: Number(form.preco_base),
            custo_estimado: form.custo_estimado !== '' ? Number(form.custo_estimado) : null,
            duracao_minutos: Number(form.duracao_minutos),
            descricao: form.descricao || null,
            ativo: form.ativo,
        };
        if (isEdit.value) {
            await axios.put(`/banho-tosa/servicos/${route.params.id}`, payload);
        } else {
            await axios.post('/banho-tosa/servicos', payload);
        }
        swalSuccess(isEdit.value ? 'Serviço atualizado.' : 'Serviço criado.');
        router.push({ name: 'banho-tosa.servicos.index' });
    } catch (e) {
        feedback.value = e?.response?.data?.message || 'Erro ao salvar serviço.';
    } finally {
        saving.value = false;
    }
}

onMounted(load);
</script>
