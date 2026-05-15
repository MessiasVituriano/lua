<template>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4">
                <form @submit.prevent="save">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome *</label>
                        <input type="text" class="form-control" :class="{ 'is-invalid': errors.nome }" id="nome" v-model="form.nome" required>
                        <div v-if="errors.nome" class="invalid-feedback">{{ errors.nome }}</div>
                    </div>

                    <div class="mb-3">
                        <label for="endereco" class="form-label">Endereco</label>
                        <input type="text" class="form-control" id="endereco" v-model="form.endereco">
                    </div>

                    <div class="mb-3">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="text" class="form-control" id="telefone" v-model="form.telefone">
                    </div>

                    <div v-if="isEdit" class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="ativa" v-model="form.ativa">
                            <label class="form-check-label" for="ativa">Loja ativa</label>
                        </div>
                    </div>

                    <div v-if="isEdit" class="mb-3 pt-2 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Dias de funcionamento</h6>
                            <small class="text-muted">Usado no cálculo das metas diárias</small>
                        </div>
                        <div class="dias-grid">
                            <label v-for="dia in diasSemana" :key="dia.value" class="form-check dias-item">
                                <input class="form-check-input" type="checkbox" v-model="calendario[dia.value]">
                                <span class="form-check-label">{{ dia.label }}</span>
                            </label>
                        </div>
                        <small v-if="calendarError" class="text-danger d-block mt-2">{{ calendarError }}</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-lua" :disabled="loading">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-check-lg"></i> {{ isEdit ? 'Atualizar' : 'Cadastrar' }}
                        </button>
                        <router-link :to="{ name: 'lojas.index' }" class="btn btn-outline-secondary">Cancelar</router-link>
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
import { useAuthStore } from '../../stores/auth';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const loading = ref(false);
const calendarError = ref('');
const errors = reactive({});
const isEdit = computed(() => !!route.params.id);
const form = reactive({ nome: '', endereco: '', telefone: '', ativa: true });
const diasSemana = [
    { value: 'segunda', label: 'Segunda' },
    { value: 'terca', label: 'Terça' },
    { value: 'quarta', label: 'Quarta' },
    { value: 'quinta', label: 'Quinta' },
    { value: 'sexta', label: 'Sexta' },
    { value: 'sabado', label: 'Sábado' },
    { value: 'domingo', label: 'Domingo' },
];
const calendario = reactive({
    segunda: false,
    terca: false,
    quarta: false,
    quinta: false,
    sexta: false,
    sabado: false,
    domingo: false,
});

onMounted(async () => {
    if (isEdit.value) {
        const [{ data: lojaData }, { data: calendarioData }] = await Promise.all([
            axios.get('/lojas/' + route.params.id),
            axios.get('/lojas/' + route.params.id + '/calendario'),
        ]);

        Object.assign(form, lojaData);
        const ativos = new Set((calendarioData.calendario || [])
            .filter(item => item.ativa)
            .map(item => item.dia_semana));
        diasSemana.forEach((dia) => {
            calendario[dia.value] = ativos.has(dia.value);
        });
    }
});

async function save() {
    Object.keys(errors).forEach(k => delete errors[k]);
    calendarError.value = '';
    loading.value = true;
    try {
        if (isEdit.value) {
            await axios.put('/lojas/' + route.params.id, form);

            const dias_ativos = diasSemana
                .filter((dia) => calendario[dia.value])
                .map((dia) => dia.value);
            await axios.post('/lojas/' + route.params.id + '/calendario', { dias_ativos });

            swalSuccess('Loja atualizada com sucesso.');
        } else {
            await axios.post('/lojas', form);
            swalSuccess('Loja criada com sucesso.');
            await auth.fetchUser();
        }
        router.push({ name: 'lojas.index' });
    } catch (e) {
        if (e.response?.status === 422) {
            const apiErrors = Object.fromEntries(
                Object.entries(e.response.data.errors).map(([k, v]) => [k, v[0]])
            );
            Object.assign(errors, apiErrors);
            if (apiErrors.dias_ativos || apiErrors['dias_ativos.0']) {
                calendarError.value = apiErrors.dias_ativos || apiErrors['dias_ativos.0'];
            }
        }
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>
.dias-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.4rem 0.75rem;
}

.dias-item {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .dias-grid {
        grid-template-columns: 1fr;
    }
}
</style>
