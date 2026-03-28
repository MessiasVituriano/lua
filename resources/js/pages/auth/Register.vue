<template>
    <form @submit.prevent="handleRegister">
        <div class="mb-3">
            <label for="name" class="form-label">Nome</label>
            <input type="text" class="form-control" :class="{ 'is-invalid': errors.name }" id="name" v-model="form.name" required autofocus>
            <div v-if="errors.name" class="invalid-feedback">{{ errors.name }}</div>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" class="form-control" :class="{ 'is-invalid': errors.email }" id="email" v-model="form.email" required>
            <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Senha</label>
            <input type="password" class="form-control" :class="{ 'is-invalid': errors.password }" id="password" v-model="form.password" required>
            <div v-if="errors.password" class="invalid-feedback">{{ errors.password }}</div>
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirmar Senha</label>
            <input type="password" class="form-control" id="password_confirmation" v-model="form.password_confirmation" required>
        </div>

        <button type="submit" class="btn btn-lua w-100 py-2" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="bi bi-person-plus-fill"></i> Cadastrar
        </button>

        <div class="text-center mt-3">
            <router-link :to="{ name: 'login' }" class="text-decoration-none small">Ja tenho uma conta</router-link>
        </div>
    </form>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const auth = useAuthStore();
const loading = ref(false);
const errors = reactive({});
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' });

async function handleRegister() {
    Object.keys(errors).forEach(k => delete errors[k]);
    loading.value = true;
    try {
        await auth.register(form);
        router.push({ name: 'dashboard' });
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

<style scoped>
.btn-lua { background: #6f42c1; color: #fff; border: none; }
.btn-lua:hover { background: #5a32a3; color: #fff; }
</style>
