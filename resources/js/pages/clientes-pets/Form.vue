<template>
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card p-4">
                <form @submit.prevent="save">
                    <h6 class="mb-3 d-flex align-items-center gap-2 section-title">
                        <i class="bi bi-person-vcard"></i>
                        <span>Cliente</span>
                    </h6>

                    <div v-if="!isEdit" class="mb-3">
                        <label class="form-label"><i class="bi bi-person-check me-1"></i>Cliente existente (opcional)</label>
                        <select class="form-select" v-model.number="form.cliente_id" @change="onClienteChange">
                            <option :value="null">Criar novo cliente</option>
                            <option v-for="c in clientes" :key="c.id" :value="c.id">
                                {{ c.nome }}{{ c.telefone ? ' - ' + c.telefone : '' }}
                            </option>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label"><i class="bi bi-person me-1"></i>Nome do cliente *</label>
                            <input type="text" class="form-control" :class="{ 'is-invalid': errors.cliente_nome }" v-model="form.cliente_nome" :disabled="!isEdit && !!form.cliente_id">
                            <div v-if="errors.cliente_nome" class="invalid-feedback">{{ errors.cliente_nome }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><i class="bi bi-whatsapp me-1"></i>Telefone</label>
                            <input type="text" class="form-control" v-model="form.cliente_telefone" :disabled="!isEdit && !!form.cliente_id">
                        </div>
                    </div>

                    <hr>
                    <h6 class="mb-3 d-flex align-items-center gap-2 section-title">
                        <i class="bi bi-heart"></i>
                        <span>Pet</span>
                    </h6>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-bookmark-heart me-1"></i>Nome do pet *</label>
                            <input type="text" class="form-control" :class="{ 'is-invalid': errors.nome }" v-model="form.nome" required>
                            <div v-if="errors.nome" class="invalid-feedback">{{ errors.nome }}</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><i class="bi bi-grid me-1"></i>Tipo</label>
                            <select class="form-select" v-model="form.tipo">
                                <option :value="null">-</option>
                                <option value="cao">Cão</option>
                                <option value="gato">Gato</option>
                                <option value="outros">Outros</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><i class="bi bi-rulers me-1"></i>Porte</label>
                            <select class="form-select" v-model="form.porte">
                                <option :value="null">-</option>
                                <option value="pequeno">Pequeno</option>
                                <option value="medio">Médio</option>
                                <option value="grande">Grande</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-award me-1"></i>Raça</label>
                            <input type="text" class="form-control" v-model="form.raca">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><i class="bi bi-calendar3 me-1"></i>Idade (meses)</label>
                            <input type="number" min="0" max="400" class="form-control" v-model.number="form.idade_meses">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="ativo" v-model="form.ativo">
                                <label class="form-check-label" for="ativo"><i class="bi bi-toggle-on me-1"></i>Pet ativo</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-lua" :disabled="loading">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-check-lg"></i>
                            {{ isEdit ? 'Atualizar' : 'Cadastrar' }}
                        </button>
                        <router-link :to="{ name: 'clientes-pets.index' }" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Cancelar
                        </router-link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { swalSuccess } from '../../utils/swal';

const route = useRoute();
const router = useRouter();

const isEdit = computed(() => !!route.params.id);
const loading = ref(false);
const clientes = ref([]);
const errors = reactive({});

const form = reactive({
    cliente_id: null,
    cliente_nome: '',
    cliente_telefone: '',
    nome: '',
    tipo: null,
    porte: null,
    raca: '',
    idade_meses: null,
    ativo: true,
});

onMounted(async () => {
    const { data: clientesData } = await axios.get('/clientes-pets/clientes-list');
    clientes.value = clientesData || [];

    if (isEdit.value) {
        const { data } = await axios.get('/clientes-pets/' + route.params.id);
        form.cliente_id = data.cliente?.id || null;
        form.cliente_nome = data.cliente?.nome || '';
        form.cliente_telefone = data.cliente?.telefone || '';
        form.nome = data.nome;
        form.tipo = data.tipo;
        form.porte = data.porte;
        form.raca = data.raca || '';
        form.idade_meses = data.idade_meses;
        form.ativo = !!data.ativo;
    }
});

function onClienteChange() {
    if (!form.cliente_id) return;
    const c = clientes.value.find(x => Number(x.id) === Number(form.cliente_id));
    if (!c) return;

    form.cliente_nome = c.nome || '';
    form.cliente_telefone = c.telefone || '';
}

async function save() {
    Object.keys(errors).forEach(k => delete errors[k]);
    loading.value = true;

    const payload = {
        cliente_id: !isEdit.value ? (form.cliente_id || null) : (form.cliente_id || null),
        cliente_nome: form.cliente_nome || null,
        cliente_telefone: form.cliente_telefone || null,
        nome: form.nome,
        tipo: form.tipo || null,
        porte: form.porte || null,
        raca: form.raca || null,
        idade_meses: Number.isFinite(Number(form.idade_meses)) ? Number(form.idade_meses) : null,
        ativo: !!form.ativo,
    };

    try {
        if (isEdit.value) {
            await axios.put('/clientes-pets/' + route.params.id, payload);
            swalSuccess('Cadastro atualizado com sucesso.');
        } else {
            await axios.post('/clientes-pets', payload);
            swalSuccess('Cadastro criado com sucesso.');
        }

        router.push({ name: 'clientes-pets.index' });
    } catch (e) {
        if (e.response?.status === 422) {
            Object.assign(errors, Object.fromEntries(
                Object.entries(e.response.data.errors || {}).map(([k, v]) => [k, v[0]])
            ));
        }
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>
.section-title {
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    background: #f3f7ff;
    color: #1f3f73;
    border: 1px solid #dbe8ff;
    width: fit-content;
}
</style>
