<template>
    <!-- Overlay mobile -->
    <div v-if="sidebarOpen" class="sidebar-overlay d-lg-none" @click="sidebarOpen = false"></div>

    <!-- Sidebar -->
    <nav class="sidebar" :class="{ open: sidebarOpen }">
        <div class="brand d-flex justify-content-between align-items-center">
            <span><i class="bi bi-heart-pulse-fill"></i> LUA PetShop</span>
            <button class="btn btn-sm text-white d-lg-none" @click="sidebarOpen = false">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <ul class="nav flex-column mt-3">
            <!-- Admin only -->
            <template v-if="isAdmin">
                <li class="nav-item">
                    <router-link class="nav-link" active-class="active" :to="{ name: 'dashboard' }" @click="closeMobile">
                        <i class="bi bi-grid-1x2-fill"></i> Dashboard
                    </router-link>
                </li>
                <li class="nav-item">
                    <router-link class="nav-link" active-class="active" :to="{ name: 'lojas.index' }" @click="closeMobile">
                        <i class="bi bi-shop"></i> Lojas
                    </router-link>
                </li>
                <li class="nav-item">
                    <router-link class="nav-link" active-class="active" :to="{ name: 'bancos.index' }" @click="closeMobile">
                        <i class="bi bi-bank2"></i> Bancos
                    </router-link>
                </li>
            </template>

            <li class="nav-item">
                <router-link class="nav-link" active-class="active" :to="{ name: 'fornecedores.index' }" @click="closeMobile">
                    <i class="bi bi-truck"></i> Fornecedores
                </router-link>
            </li>

            <li class="nav-item mt-2 px-3"><small class="sidebar-section">Financeiro</small></li>
            <li class="nav-item">
                <router-link class="nav-link" active-class="active" :to="{ name: 'caixa.hoje' }" @click="closeMobile">
                    <i class="bi bi-cash-register"></i> Caixa do Dia
                </router-link>
            </li>
            <template v-if="isAdmin">
                <li class="nav-item">
                    <router-link class="nav-link" active-class="active" :to="{ name: 'caixa.historico' }" @click="closeMobile">
                        <i class="bi bi-clock-history"></i> Historico Caixa
                    </router-link>
                </li>
                <li class="nav-item">
                    <router-link class="nav-link" active-class="active" :to="{ name: 'pagamentos.index' }" @click="closeMobile">
                        <i class="bi bi-calendar-check"></i> Pagamentos
                        <span v-if="alertCount > 0" class="badge bg-danger ms-1 rounded-pill">{{ alertCount }}</span>
                    </router-link>
                </li>
            </template>

            <li class="nav-item mt-2 px-3"><small class="sidebar-section">Estoque</small></li>
            <li class="nav-item">
                <router-link class="nav-link" active-class="active" :to="{ name: 'produtos.index' }" @click="closeMobile">
                    <i class="bi bi-box-seam"></i> Produtos
                </router-link>
            </li>

            <template v-if="isAdmin">
                <li class="nav-item mt-2 px-3"><small class="sidebar-section">Sistema</small></li>
                <li class="nav-item">
                    <router-link class="nav-link" active-class="active" :to="{ name: 'usuarios.index' }" @click="closeMobile">
                        <i class="bi bi-people-fill"></i> Usuarios
                    </router-link>
                </li>
            </template>
        </ul>
    </nav>

    <!-- Main -->
    <div class="main-content">
        <div class="topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" @click="sidebarOpen = !sidebarOpen">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="mb-0 d-none d-sm-block">{{ pageTitle }}</h5>
            </div>
            <div class="d-flex align-items-center gap-2">
                <select
                    v-if="auth.lojas.length > 1"
                    class="form-select form-select-sm loja-select d-none d-md-block"
                    :value="auth.user?.loja_id"
                    @change="switchLoja($event.target.value)"
                >
                    <option v-for="loja in auth.lojas" :key="loja.id" :value="loja.id">
                        {{ loja.nome }}
                    </option>
                </select>
                <span v-else-if="auth.lojaAtiva" class="text-muted small d-none d-md-inline">
                    <i class="bi bi-shop"></i> {{ auth.lojaAtiva.nome }}
                </span>

                <button class="theme-toggle" @click="theme.toggle()" :title="theme.mode === 'light' ? 'Modo escuro' : 'Modo claro'">
                    <i :class="theme.mode === 'light' ? 'bi bi-moon-fill' : 'bi bi-sun-fill'"></i>
                </button>

                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <span class="d-none d-sm-inline">{{ auth.user?.name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="dropdown-item-text small text-muted">
                            <i class="bi bi-shield-check"></i> {{ auth.user?.role === 'admin' ? 'Administrador' : 'Atendente' }}
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><button class="dropdown-item" @click="handleLogout"><i class="bi bi-box-arrow-right"></i> Sair</button></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="content-area">
            <!-- Banner pendencias admin -->
            <div v-if="isAdmin && caixasPendentes.length > 0" class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div class="flex-grow-1">
                    <strong>{{ caixasPendentes.length }} caixa(s) pendente(s) de aprovacao!</strong>
                    <div class="small mt-1">
                        <span v-for="(cp, i) in caixasPendentes" :key="cp.id">
                            {{ fmtDateShort(cp.data) }} (R$ {{ fmtMoney(cp.total_entradas) }} - {{ cp.fechado_por?.name }})
                            <span v-if="i < caixasPendentes.length - 1"> | </span>
                        </span>
                    </div>
                </div>
                <router-link :to="{ name: 'caixa.historico' }" class="btn btn-sm btn-warning">
                    <i class="bi bi-check-circle"></i> Revisar
                </router-link>
            </div>

            <Transition name="fade" mode="out-in">
                <div v-if="notification.show" :class="'alert alert-' + notification.type + ' alert-dismissible fade show'" role="alert">
                    {{ notification.message }}
                    <button type="button" class="btn-close" @click="notification.show = false"></button>
                </div>
            </Transition>

            <router-view />
        </div>
    </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useNotificationStore } from '../stores/notification';
import { useThemeStore } from '../stores/theme';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const notification = useNotificationStore();
const theme = useThemeStore();
const alertCount = ref(0);
const sidebarOpen = ref(false);
const caixasPendentes = ref([]);
let pendentesTimer = null;

const isAdmin = computed(() => auth.user?.role === 'admin');

const titles = {
    'dashboard': 'Dashboard',
    'lojas.index': 'Lojas', 'lojas.create': 'Nova Loja', 'lojas.edit': 'Editar Loja', 'lojas.usuarios': 'Usuarios da Loja',
    'bancos.index': 'Bancos', 'bancos.create': 'Novo Banco', 'bancos.edit': 'Editar Banco',
    'fornecedores.index': 'Fornecedores', 'fornecedores.create': 'Novo Fornecedor', 'fornecedores.show': 'Detalhes do Fornecedor', 'fornecedores.edit': 'Editar Fornecedor',
    'usuarios.index': 'Usuarios', 'usuarios.create': 'Novo Usuario', 'usuarios.edit': 'Editar Usuario',
    'caixa.hoje': 'Caixa do Dia', 'caixa.historico': 'Historico de Caixa', 'caixa.show': 'Detalhes do Caixa',
    'pagamentos.index': 'Pagamentos', 'pagamentos.create': 'Novo Pagamento', 'pagamentos.edit': 'Editar Pagamento',
    'produtos.index': 'Produtos', 'produtos.create': 'Novo Produto', 'produtos.edit': 'Editar Produto', 'produtos.show': 'Detalhes do Produto',
};

const pageTitle = computed(() => titles[route.name] || 'LUA');

function closeMobile() { sidebarOpen.value = false; }

function fmtDateShort(d) {
    if (!d) return '';
    const s = typeof d === 'string' ? d.slice(0, 10) : d;
    return new Date(s + 'T12:00:00').toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
}

function fmtMoney(v) { return Number(v || 0).toFixed(2).replace('.', ','); }

async function loadPendentes() {
    if (!isAdmin.value) return;
    try {
        const { data } = await axios.get('/caixa-pendentes');
        caixasPendentes.value = data;
    } catch {}
}

onMounted(async () => {
    try {
        const { data } = await axios.get('/pagamentos-alertas');
        alertCount.value = data.total_atrasados + data.total_proximos;
    } catch {}

    // Carregar pendencias e fazer polling a cada 30s
    await loadPendentes();
    if (isAdmin.value) {
        pendentesTimer = setInterval(loadPendentes, 30000);
    }
});

onUnmounted(() => {
    if (pendentesTimer) clearInterval(pendentesTimer);
});

async function switchLoja(lojaId) {
    await auth.switchLoja(lojaId);
    notification.success('Loja alterada com sucesso.');
}

async function handleLogout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>

<style scoped>
.sidebar {
    width: 250px;
    min-height: 100vh;
    background: var(--lua-sidebar);
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1050;
    overflow-y: auto;
}
.sidebar .brand {
    padding: 1rem 1.2rem;
    font-size: 1.3rem;
    font-weight: 700;
    color: #fff;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.sidebar .brand i { color: #a78bfa; }
.sidebar .nav-link {
    color: rgba(255,255,255,0.7);
    padding: 0.6rem 1.2rem;
    font-size: 0.88rem;
}
.sidebar .nav-link:hover,
.sidebar .nav-link.active {
    color: #fff;
    background: var(--lua-sidebar-hover);
}
.sidebar .nav-link i { width: 22px; display: inline-block; }
.sidebar-section {
    color: rgba(255,255,255,0.35);
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.sidebar-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1040;
}
.main-content { margin-left: 250px; }
.topbar {
    background: var(--lua-topbar);
    border-bottom: 1px solid var(--lua-topbar-border);
    padding: 0.8rem 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.loja-select {
    border: 1px solid var(--lua-input-border);
    border-radius: 8px;
    padding: 0.4rem 0.8rem;
    font-size: 0.85rem;
    width: auto;
    background: var(--lua-input-bg);
    color: var(--lua-text);
}
.content-area { padding: 1.2rem; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* Mobile */
@media (max-width: 991.98px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    .sidebar.open {
        transform: translateX(0);
    }
    .main-content {
        margin-left: 0;
    }
    .content-area {
        padding: 0.8rem;
    }
}
</style>
