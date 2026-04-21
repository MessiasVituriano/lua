<template>
    <div class="login-header">
        <h1 class="login-title">Entrar</h1>
        <p class="login-subtitle">Acesse sua conta para continuar</p>
    </div>

    <div v-if="errors.general" class="error-banner">
        <AlertCircle :size="14" />
        <span>{{ errors.general }}</span>
    </div>

    <form @submit.prevent="handleLogin" class="login-form">
        <div class="field">
            <label for="email" class="form-label">E-mail</label>
            <input
                type="email"
                class="form-control"
                :class="{ 'is-invalid': errors.email }"
                id="email"
                v-model="form.email"
                placeholder="voce@empresa.com"
                required
                autofocus
            >
            <div v-if="errors.email" class="invalid-feedback d-block">{{ errors.email }}</div>
        </div>

        <div class="field">
            <label for="password" class="form-label">Senha</label>
            <div class="password-wrap">
                <input
                    :type="showPassword ? 'text' : 'password'"
                    class="form-control"
                    id="password"
                    v-model="form.password"
                    placeholder="••••••••"
                    required
                >
                <button
                    type="button"
                    class="password-toggle"
                    @click="showPassword = !showPassword"
                    :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                    :title="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                    tabindex="-1"
                >
                    <EyeOff v-if="showPassword" :size="16" />
                    <Eye v-else :size="16" />
                </button>
            </div>
        </div>

        <div class="remember-row">
            <label class="remember">
                <input type="checkbox" v-model="form.remember">
                <span>Lembrar de mim</span>
            </label>
        </div>

        <button type="submit" class="btn btn-lua w-100 btn-submit" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm"></span>
            <span v-else>Entrar</span>
            <ArrowRight v-if="!loading" :size="16" />
        </button>
    </form>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { ArrowRight, AlertCircle, Eye, EyeOff } from 'lucide-vue-next';

const router = useRouter();
const auth = useAuthStore();
const loading = ref(false);
const showPassword = ref(false);
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
            errors.general = 'Credenciais inválidas.';
        }
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>
.login-header {
    margin-bottom: 1.5rem;
}
.login-title {
    font-family: 'Inter Tight', 'Inter', sans-serif;
    font-size: 1.5rem;
    font-weight: 600;
    letter-spacing: -0.022em;
    color: var(--lua-text);
    margin: 0 0 0.25rem 0;
}
.login-subtitle {
    font-size: 0.875rem;
    color: var(--lua-text-muted);
    margin: 0;
}

.error-banner {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--lua-danger-soft);
    color: var(--lua-danger);
    border-radius: 8px;
    padding: 0.625rem 0.75rem;
    font-size: 0.8125rem;
    margin-bottom: 1rem;
}

.login-form { display: flex; flex-direction: column; gap: 1rem; }
.field { display: flex; flex-direction: column; }

.remember-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: -0.25rem;
}
.remember {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: var(--lua-text-soft);
    cursor: pointer;
    user-select: none;
}
.remember input[type="checkbox"] {
    accent-color: var(--lua-primary);
    width: 14px;
    height: 14px;
}

.btn-submit {
    justify-content: center;
    padding: 0.65rem 1rem;
    font-weight: 500;
    font-size: 0.9rem;
    margin-top: 0.25rem;
}
.btn-submit .spinner-border { width: 14px; height: 14px; border-width: 2px; }

.password-wrap { position: relative; }
.password-wrap .form-control { padding-right: 2.5rem; }
.password-toggle {
    position: absolute;
    top: 50%;
    right: 0.5rem;
    transform: translateY(-50%);
    background: none;
    border: none;
    padding: 0.35rem;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--lua-text-muted);
    cursor: pointer;
    transition: color 0.12s, background 0.12s;
}
.password-toggle:hover { color: var(--lua-text); background: var(--lua-surface-muted); }
.password-toggle:focus-visible {
    outline: 2px solid var(--lua-input-focus-ring);
    outline-offset: 1px;
}
</style>
