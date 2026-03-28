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
const errors = reactive({});
const isEdit = computed(() => !!route.params.id);
const form = reactive({ nome: '', endereco: '', telefone: '', ativa: true });

onMounted(async () => {
    if (isEdit.value) {
        const { data } = await axios.get('/lojas/' + route.params.id);
        Object.assign(form, data);
    }
});

async function save() {
    Object.keys(errors).forEach(k => delete errors[k]);
    loading.value = true;
    try {
        if (isEdit.value) {
            await axios.put('/lojas/' + route.params.id, form);
            swalSuccess('Loja atualizada com sucesso.');
        } else {
            await axios.post('/lojas', form);
            swalSuccess('Loja criada com sucesso.');
            await auth.fetchUser();
        }
        router.push({ name: 'lojas.index' });
    } catch (e) {
        if (e.response?.status === 422) {
            Object.assign(errors, Object.fromEntries(
                Object.entries(e.response.data.errors).map(([k, v]) => [k, v[0]])
            ));
        }
    } finally {
        loading.value = false;
    }
}
</script>
