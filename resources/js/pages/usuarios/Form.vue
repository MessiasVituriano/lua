<template>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4">
                <form @submit.prevent="save">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome *</label>
                        <input type="text" class="form-control" :class="{ 'is-invalid': errors.name }" id="name" v-model="form.name" required>
                        <div v-if="errors.name" class="invalid-feedback">{{ errors.name }}</div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail *</label>
                        <input type="email" class="form-control" :class="{ 'is-invalid': errors.email }" id="email" v-model="form.email" required>
                        <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            Senha {{ isEdit ? '(deixe vazio para manter)' : '*' }}
                        </label>
                        <input type="password" class="form-control" :class="{ 'is-invalid': errors.password }" id="password" v-model="form.password" :required="!isEdit">
                        <div v-if="errors.password" class="invalid-feedback">{{ errors.password }}</div>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirmar Senha</label>
                        <input type="password" class="form-control" id="password_confirmation" v-model="form.password_confirmation">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Lojas</label>
                        <div class="row">
                            <div class="col-md-6" v-for="loja in lojas" :key="loja.id">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" :value="loja.id" :id="'loja_' + loja.id" v-model="form.lojas">
                                    <label class="form-check-label" :for="'loja_' + loja.id">{{ loja.nome }}</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Perfil *</label>
                        <select class="form-select" v-model="form.role" required>
                            <option value="admin">Administrador</option>
                            <option value="atendente">Atendente</option>
                        </select>
                    </div>

                    <div v-if="isEdit" class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="ativo" v-model="form.ativo">
                            <label class="form-check-label" for="ativo">Usuario ativo</label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-lua" :disabled="loading">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-check-lg"></i> {{ isEdit ? 'Atualizar' : 'Cadastrar' }}
                        </button>
                        <router-link :to="{ name: 'usuarios.index' }" class="btn btn-outline-secondary">Cancelar</router-link>
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
const lojas = ref([]);
const form = reactive({ name: '', email: '', password: '', password_confirmation: '', ativo: true, role: 'atendente', lojas: [] });

onMounted(async () => {
    const { data: lojasData } = await axios.get('/lojas-list');
    lojas.value = lojasData;

    if (isEdit.value) {
        const { data } = await axios.get('/usuarios/' + route.params.id);
        form.name = data.name;
        form.email = data.email;
        form.ativo = data.ativo;
        form.role = data.role;
        form.lojas = data.lojas.map(l => l.id);
    }
});

async function save() {
    Object.keys(errors).forEach(k => delete errors[k]);
    loading.value = true;
    try {
        if (isEdit.value) {
            await axios.put('/usuarios/' + route.params.id, form);
            swalSuccess('Usuario atualizado com sucesso.');
        } else {
            await axios.post('/usuarios', form);
            swalSuccess('Usuario criado com sucesso.');
        }
        router.push({ name: 'usuarios.index' });
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
