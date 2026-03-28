import { defineStore } from 'pinia';

export const useThemeStore = defineStore('theme', {
    state: () => ({
        mode: localStorage.getItem('lua-theme') || 'light',
    }),

    actions: {
        toggle() {
            this.mode = this.mode === 'light' ? 'dark' : 'light';
            this.apply();
        },

        apply() {
            document.documentElement.setAttribute('data-bs-theme', this.mode);
            localStorage.setItem('lua-theme', this.mode);
        },

        init() {
            this.apply();
        },
    },
});
