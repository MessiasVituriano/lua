import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        loaded: false,
    }),

    getters: {
        lojaAtiva: (state) => state.user?.loja_ativa,
        lojas: (state) => state.user?.lojas || [],
    },

    actions: {
        async fetchUser() {
            try {
                const { data } = await axios.get('/user');
                this.user = data;
            } catch {
                this.user = null;
            } finally {
                this.loaded = true;
            }
        },

        async login(credentials) {
            await axios.get('/sanctum/csrf-cookie', { baseURL: '/' });
            await axios.post('/login', credentials, { baseURL: '/' });
            await this.fetchUser();
        },

        async register(data) {
            await axios.get('/sanctum/csrf-cookie', { baseURL: '/' });
            await axios.post('/register', data, { baseURL: '/' });
            await this.fetchUser();
        },

        async logout() {
            await axios.post('/logout', {}, { baseURL: '/' });
            this.user = null;
        },

        async switchLoja(lojaId) {
            await axios.post('/switch-loja', { loja_id: lojaId }, { baseURL: '/' });
            await this.fetchUser();
        },
    },
});
