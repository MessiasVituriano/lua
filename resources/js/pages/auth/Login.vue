<template>
    <div v-if="errors.general" class="alert alert-danger py-2 small">{{ errors.general }}</div>

    <form @submit.prevent="handleLogin">
        <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" class="form-control" :class="{ 'is-invalid': errors.email }" id="email" v-model="form.email" required autofocus>
            <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Senha</label>
            <input type="password" class="form-control" id="password" v-model="form.password" required>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="remember" v-model="form.remember">
            <label class="form-check-label" for="remember">Lembrar de mim</label>
        </div>

        <button type="submit" class="btn btn-lua w-100 py-2" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="bi bi-box-arrow-in-right"></i> Entrar
        </button>

        <div class="text-center mt-3">
            <router-link :to="{ name: 'register' }" class="text-decoration-none small">Criar uma conta</router-link>
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
const errors = reactive({ email: '', general: '' });
const form = reactive({ email: '', password: '', remember: false });

async function handleLogin() {
    errors.email = '';
    errors.general = '';
    loading.value = true;
    try {
        await auth.login(form);
        router.push({ name: 'dashboard' });
    } catch (e) {
        if (e.response?.status === 422) {
            const msgs = e.response.data.errors;
            errors.email = msgs?.email?.[0] || '';
        } else {
            errors.general = 'Credenciais invalidas.';
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
