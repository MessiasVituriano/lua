<template>
    <div v-if="sidebarOpen" class="sidebar-overlay d-lg-none" @click="sidebarOpen = false"></div>

    <nav class="sidebar" :class="{ open: sidebarOpen }">
        <div class="sidebar-brand">
            <div class="brand-group">
                <div class="brand-mark">
                    <Moon :size="16" :stroke-width="2" />
                </div>
                <div class="brand-meta">
                    <div class="brand-name">LUA</div>
                    <div class="brand-tagline">PetShop</div>
                </div>
            </div>
            <button class="sidebar-close d-lg-none" @click="sidebarOpen = false" aria-label="Fechar">
                <X :size="18" />
            </button>
        </div>

        <div class="sidebar-nav">
            <template v-if="isAdmin">
                <router-link class="nav-item" active-class="active" :to="{ name: 'dashboard' }" @click="closeMobile">
                    <LayoutDashboard :size="16" class="nav-icon" />
                    <span>Dashboard</span>
                </router-link>
                <router-link class="nav-item" active-class="active" :to="{ name: 'lojas.index' }" @click="closeMobile">
                    <Store :size="16" class="nav-icon" />
                    <span>Lojas</span>
                </router-link>
                <router-link class="nav-item" active-class="active" :to="{ name: 'bancos.index' }" @click="closeMobile">
                    <Building2 :size="16" class="nav-icon" />
                    <span>Bancos</span>
                </router-link>
            </template>

            <router-link class="nav-item" active-class="active" :to="{ name: 'fornecedores.index' }" @click="closeMobile">
                <Truck :size="16" class="nav-icon" />
                <span>Fornecedores</span>
            </router-link>

            <div class="nav-section">Financeiro</div>

            <router-link class="nav-item" active-class="active" :to="{ name: 'caixa.hoje' }" @click="closeMobile">
                <Wallet :size="16" class="nav-icon" />
                <span>Caixa do Dia</span>
            </router-link>
            <router-link class="nav-item" active-class="active" :to="{ name: 'movimentacoes.index' }" @click="closeMobile">
                <ArrowLeftRight :size="16" class="nav-icon" />
                <span>Movimentações</span>
                <span v-if="isAdmin && movPendentesCount > 0" class="nav-badge warning">{{ movPendentesCount }}</span>
            </router-link>
            <template v-if="isAdmin">
                <router-link class="nav-item" active-class="active" :to="{ name: 'caixa.historico' }" @click="closeMobile">
                    <History :size="16" class="nav-icon" />
                    <span>Histórico Caixa</span>
                </router-link>
                <router-link class="nav-item" active-class="active" :to="{ name: 'pagamentos.index' }" @click="closeMobile">
                    <CalendarCheck :size="16" class="nav-icon" />
                    <span>Pagamentos</span>
                    <span v-if="alertAtrasados > 0" class="nav-badge danger" title="Atrasados">{{ alertAtrasados }}</span>
                    <span v-if="alertProximos > 0" class="nav-badge warning" title="Vencendo em 7 dias">{{ alertProximos }}</span>
                </router-link>
                <router-link class="nav-item" active-class="active" :to="{ name: 'pagamentos.calendario' }" @click="closeMobile">
                    <CalendarDays :size="16" class="nav-icon" />
                    <span>Calendário</span>
                </router-link>
            </template>

            <div class="nav-section">Estoque</div>
            <router-link class="nav-item" active-class="active" :to="{ name: 'produtos.index' }" @click="closeMobile">
                <Package :size="16" class="nav-icon" />
                <span>Produtos</span>
            </router-link>

            <template v-if="isAdmin">
                <div class="nav-section">Sistema</div>
                <router-link class="nav-item" active-class="active" :to="{ name: 'usuarios.index' }" @click="closeMobile">
                    <Users :size="16" class="nav-icon" />
                    <span>Usuários</span>
                </router-link>
            </template>
        </div>

        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-avatar">{{ userInitials }}</div>
                <div class="user-info">
                    <div class="user-name">{{ auth.user?.name }}</div>
                    <div class="user-role">{{ auth.user?.role === 'admin' ? 'Administrador' : 'Atendente' }}</div>
                </div>
                <button class="user-logout" @click="handleLogout" title="Sair">
                    <LogOut :size="15" />
                </button>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <button class="icon-btn d-lg-none" @click="sidebarOpen = !sidebarOpen" aria-label="Menu">
                    <Menu :size="18" />
                </button>
                <h5 class="topbar-title d-none d-sm-block">{{ pageTitle }}</h5>
            </div>
            <div class="topbar-right">
                <select
                    v-if="auth.lojas.length > 1"
                    class="loja-select d-none d-md-block"
                    :value="auth.user?.loja_id"
                    @change="switchLoja($event.target.value)"
                >
                    <option v-for="loja in auth.lojas" :key="loja.id" :value="loja.id">
                        {{ loja.nome }}
                    </option>
                </select>
                <span v-else-if="auth.lojaAtiva" class="loja-label d-none d-md-inline">
                    <Store :size="14" />
                    {{ auth.lojaAtiva.nome }}
                </span>

                <button class="theme-toggle" @click="theme.toggle()" :title="theme.mode === 'light' ? 'Modo escuro' : 'Modo claro'">
                    <Moon v-if="theme.mode === 'light'" :size="15" />
                    <Sun v-else :size="15" />
                </button>
            </div>
        </div>

        <div class="content-area">
            <div v-if="isAdmin && caixasPendentes.length > 0" class="pendencia-banner">
                <AlertTriangle :size="18" />
                <div class="flex-grow-1">
                    <strong>{{ caixasPendentes.length }} caixa(s) pendente(s) de aprovação</strong>
                    <div class="small">
                        <span v-for="(cp, i) in caixasPendentes" :key="cp.id">
                            {{ fmtDateShort(cp.data) }} (R$ {{ fmtMoney(cp.total_entradas) }} · {{ cp.fechado_por?.name }})
                            <span v-if="i < caixasPendentes.length - 1"> · </span>
                        </span>
                    </div>
                </div>
                <router-link :to="{ name: 'caixa.historico' }" class="btn btn-sm btn-lua">
                    Revisar
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
import {
    Moon, Sun, Menu, X, LogOut, Store, Building2, Truck, Wallet,
    ArrowLeftRight, History, CalendarCheck, CalendarDays, Package,
    Users, LayoutDashboard, AlertTriangle
} from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const notification = useNotificationStore();
const theme = useThemeStore();
const alertCount = ref(0);
const alertAtrasados = ref(0);
const alertProximos = ref(0);
const movPendentesCount = ref(0);
const sidebarOpen = ref(false);
const caixasPendentes = ref([]);
let pendentesTimer = null;

const isAdmin = computed(() => auth.user?.role === 'admin');

const userInitials = computed(() => {
    const n = auth.user?.name || '';
    return n.trim().split(/\s+/).map(p => p[0]).slice(0, 2).join('').toUpperCase() || '?';
});

const titles = {
    'dashboard': 'Dashboard',
    'lojas.index': 'Lojas', 'lojas.create': 'Nova Loja', 'lojas.edit': 'Editar Loja', 'lojas.usuarios': 'Usuários da Loja',
    'bancos.index': 'Bancos', 'bancos.create': 'Novo Banco', 'bancos.edit': 'Editar Banco',
    'fornecedores.index': 'Fornecedores', 'fornecedores.create': 'Novo Fornecedor', 'fornecedores.show': 'Detalhes do Fornecedor', 'fornecedores.edit': 'Editar Fornecedor',
    'usuarios.index': 'Usuários', 'usuarios.create': 'Novo Usuário', 'usuarios.edit': 'Editar Usuário',
    'caixa.hoje': 'Caixa do Dia', 'caixa.historico': 'Histórico de Caixa', 'caixa.show': 'Detalhes do Caixa',
    'movimentacoes.index': 'Movimentações Internas', 'movimentacoes.create': 'Nova Movimentação', 'movimentacoes.edit': 'Editar Movimentação',
    'pagamentos.index': 'Pagamentos', 'pagamentos.calendario': 'Calendário de Pagamentos', 'pagamentos.create': 'Novo Pagamento', 'pagamentos.edit': 'Editar Pagamento',
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
        alertAtrasados.value = data.total_atrasados;
        alertProximos.value = data.total_proximos;
        alertCount.value = data.total_atrasados + data.total_proximos;
    } catch {}

    if (isAdmin.value) {
        try {
            const { data } = await axios.get('/movimentacoes-internas-pendentes');
            movPendentesCount.value = data.length;
        } catch {}
    }

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
/* ============ Sidebar ============ */
.sidebar {
    width: 248px;
    min-height: 100vh;
    background: var(--lua-sidebar);
    border-right: 1px solid var(--lua-sidebar-border);
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1050;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.sidebar-brand {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.9rem 1rem;
    border-bottom: 1px solid var(--lua-sidebar-border);
}
.brand-group { display: flex; align-items: center; gap: 0.625rem; }
.brand-mark {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: var(--lua-primary-soft);
    color: var(--lua-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--lua-primary-border);
}
.brand-meta { line-height: 1.1; }
.brand-name {
    font-family: 'Inter Tight', 'Inter', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: -0.015em;
    color: var(--lua-text);
}
.brand-tagline {
    font-size: 0.6875rem;
    color: var(--lua-text-muted);
    letter-spacing: 0.02em;
}
.sidebar-close {
    background: none;
    border: none;
    color: var(--lua-text-muted);
    padding: 0.25rem;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.sidebar-close:hover { background: var(--lua-sidebar-hover); color: var(--lua-text); }

.sidebar-nav {
    flex: 1;
    overflow-y: auto;
    padding: 0.75rem 0.5rem;
}

.nav-section {
    font-size: 0.6875rem;
    font-weight: 500;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--lua-text-subtle);
    padding: 0.9rem 0.7rem 0.4rem;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.5rem 0.7rem;
    border-radius: 6px;
    color: var(--lua-text-soft);
    font-size: 0.85rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.12s ease;
    position: relative;
}
.nav-item:hover {
    background: var(--lua-sidebar-hover);
    color: var(--lua-text);
}
.nav-item.active {
    background: var(--lua-sidebar-active);
    color: var(--lua-primary);
}
.nav-item.active .nav-icon { color: var(--lua-primary); }
.nav-icon { color: var(--lua-text-muted); flex-shrink: 0; }
.nav-item span { flex: 1; }

.nav-badge {
    font-size: 0.6875rem;
    font-weight: 600;
    padding: 0.1rem 0.4rem;
    border-radius: 999px;
    line-height: 1.3;
}
.nav-badge.warning { background: var(--lua-warning-soft); color: var(--lua-warning); }
.nav-badge.danger  { background: var(--lua-danger-soft); color: var(--lua-danger); }

/* ============ Sidebar footer: user ============ */
.sidebar-footer {
    border-top: 1px solid var(--lua-sidebar-border);
    padding: 0.625rem;
}
.user-card {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.45rem 0.55rem;
    border-radius: 8px;
    transition: background 0.12s;
}
.user-card:hover { background: var(--lua-sidebar-hover); }
.user-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: var(--lua-primary);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    flex-shrink: 0;
}
.user-info { flex: 1; min-width: 0; line-height: 1.2; }
.user-name {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--lua-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.user-role {
    font-size: 0.6875rem;
    color: var(--lua-text-muted);
}
.user-logout {
    background: none;
    border: none;
    color: var(--lua-text-muted);
    padding: 0.3rem;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.user-logout:hover { background: var(--lua-surface-muted); color: var(--lua-danger); }

.sidebar-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.35);
    backdrop-filter: blur(2px);
    z-index: 1040;
}

/* ============ Topbar ============ */
.main-content { margin-left: 248px; min-height: 100vh; }

.topbar {
    background: var(--lua-topbar);
    border-bottom: 1px solid var(--lua-topbar-border);
    padding: 0.65rem 1.25rem;
    height: 56px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 10;
    backdrop-filter: saturate(1.4);
}
.topbar-left, .topbar-right {
    display: flex;
    align-items: center;
    gap: 0.625rem;
}
.topbar-title {
    font-family: 'Inter Tight', 'Inter', sans-serif;
    font-size: 0.95rem;
    font-weight: 600;
    letter-spacing: -0.01em;
    color: var(--lua-text);
    margin: 0;
}
.icon-btn {
    background: none;
    border: 1px solid var(--lua-border);
    border-radius: 6px;
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--lua-text-muted);
}
.icon-btn:hover { background: var(--lua-surface-muted); color: var(--lua-text); }

.loja-select {
    border: 1px solid var(--lua-input-border);
    border-radius: 6px;
    padding: 0.35rem 0.65rem;
    font-size: 0.8125rem;
    background: var(--lua-input-bg);
    color: var(--lua-text);
    width: auto;
    min-width: 180px;
}
.loja-label {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    color: var(--lua-text-muted);
}

/* ============ Content ============ */
.content-area { padding: 1.25rem 1.5rem; max-width: 1400px; }

.pendencia-banner {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: var(--lua-warning-soft);
    color: var(--lua-warning);
    border: 1px solid transparent;
    border-radius: var(--lua-radius);
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
}
.pendencia-banner .small { color: var(--lua-warning); opacity: 0.85; margin-top: 0.2rem; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* ============ Mobile ============ */
@media (max-width: 991.98px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.25s ease;
    }
    .sidebar.open {
        transform: translateX(0);
    }
    .main-content { margin-left: 0; }
    .content-area { padding: 1rem; }
}
</style>
