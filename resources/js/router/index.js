import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

import GuestLayout from '../layouts/GuestLayout.vue';
import AppLayout from '../layouts/AppLayout.vue';

import Login from '../pages/auth/Login.vue';
import Register from '../pages/auth/Register.vue';
import Dashboard from '../pages/Dashboard.vue';
import LojasIndex from '../pages/lojas/Index.vue';
import LojasForm from '../pages/lojas/Form.vue';
import LojasUsuarios from '../pages/lojas/Usuarios.vue';
import BancosIndex from '../pages/bancos/Index.vue';
import BancosForm from '../pages/bancos/Form.vue';
import FornecedoresIndex from '../pages/fornecedores/Index.vue';
import FornecedoresForm from '../pages/fornecedores/Form.vue';
import FornecedoresShow from '../pages/fornecedores/Show.vue';
import UsuariosIndex from '../pages/usuarios/Index.vue';
import UsuariosForm from '../pages/usuarios/Form.vue';
import CaixaHoje from '../pages/caixa/Hoje.vue';
import CaixaHistorico from '../pages/caixa/Historico.vue';
import CaixaShow from '../pages/caixa/Show.vue';
import PagamentosIndex from '../pages/pagamentos/Index.vue';
import PagamentosForm from '../pages/pagamentos/Form.vue';
import ProdutosIndex from '../pages/produtos/Index.vue';
import ProdutosForm from '../pages/produtos/Form.vue';
import ProdutosShow from '../pages/produtos/Show.vue';

const routes = [
    {
        path: '/',
        component: GuestLayout,
        meta: { guest: true },
        children: [
            { path: '', redirect: '/login' },
            { path: 'login', name: 'login', component: Login },
            { path: 'register', name: 'register', component: Register },
        ],
    },
    {
        path: '/',
        component: AppLayout,
        meta: { auth: true },
        children: [
            { path: 'dashboard', name: 'dashboard', component: Dashboard },
            // Lojas
            { path: 'lojas', name: 'lojas.index', component: LojasIndex },
            { path: 'lojas/criar', name: 'lojas.create', component: LojasForm },
            { path: 'lojas/:id/editar', name: 'lojas.edit', component: LojasForm },
            { path: 'lojas/:id/usuarios', name: 'lojas.usuarios', component: LojasUsuarios },
            // Bancos
            { path: 'bancos', name: 'bancos.index', component: BancosIndex },
            { path: 'bancos/criar', name: 'bancos.create', component: BancosForm },
            { path: 'bancos/:id/editar', name: 'bancos.edit', component: BancosForm },
            // Fornecedores
            { path: 'fornecedores', name: 'fornecedores.index', component: FornecedoresIndex },
            { path: 'fornecedores/criar', name: 'fornecedores.create', component: FornecedoresForm },
            { path: 'fornecedores/:id', name: 'fornecedores.show', component: FornecedoresShow },
            { path: 'fornecedores/:id/editar', name: 'fornecedores.edit', component: FornecedoresForm },
            // Usuarios
            { path: 'usuarios', name: 'usuarios.index', component: UsuariosIndex },
            { path: 'usuarios/criar', name: 'usuarios.create', component: UsuariosForm },
            { path: 'usuarios/:id/editar', name: 'usuarios.edit', component: UsuariosForm },
            // Caixa
            { path: 'caixa', name: 'caixa.hoje', component: CaixaHoje },
            { path: 'caixa/historico', name: 'caixa.historico', component: CaixaHistorico },
            { path: 'caixa/:id', name: 'caixa.show', component: CaixaShow },
            // Pagamentos
            { path: 'pagamentos', name: 'pagamentos.index', component: PagamentosIndex },
            { path: 'pagamentos/criar', name: 'pagamentos.create', component: PagamentosForm },
            { path: 'pagamentos/:id/editar', name: 'pagamentos.edit', component: PagamentosForm },
            // Produtos
            { path: 'produtos', name: 'produtos.index', component: ProdutosIndex },
            { path: 'produtos/criar', name: 'produtos.create', component: ProdutosForm },
            { path: 'produtos/:id', name: 'produtos.show', component: ProdutosShow },
            { path: 'produtos/:id/editar', name: 'produtos.edit', component: ProdutosForm },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to, from, next) => {
    const auth = useAuthStore();

    if (!auth.loaded) {
        await auth.fetchUser();
    }

    if (to.matched.some(r => r.meta.auth) && !auth.user) {
        return next({ name: 'login' });
    }

    if (to.matched.some(r => r.meta.guest) && auth.user) {
        return next({ name: auth.user.role === 'admin' ? 'dashboard' : 'caixa.hoje' });
    }

    // Bloquear rotas admin para atendentes
    const adminRoutes = ['dashboard', 'lojas.index', 'lojas.create', 'lojas.edit', 'lojas.usuarios',
        'bancos.index', 'bancos.create', 'bancos.edit', 'usuarios.index', 'usuarios.create', 'usuarios.edit',
        'caixa.historico', 'pagamentos.index', 'pagamentos.create', 'pagamentos.edit'];
    if (auth.user && auth.user.role !== 'admin' && adminRoutes.includes(to.name)) {
        return next({ name: 'caixa.hoje' });
    }

    next();
});

export default router;
