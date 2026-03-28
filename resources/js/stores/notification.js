import { defineStore } from 'pinia';

export const useNotificationStore = defineStore('notification', {
    state: () => ({
        message: '',
        type: 'success',
        show: false,
    }),

    actions: {
        success(message) {
            this.message = message;
            this.type = 'success';
            this.show = true;
            setTimeout(() => (this.show = false), 4000);
        },

        error(message) {
            this.message = message;
            this.type = 'danger';
            this.show = true;
            setTimeout(() => (this.show = false), 5000);
        },

        warning(message) {
            this.message = message;
            this.type = 'warning';
            this.show = true;
            setTimeout(() => (this.show = false), 4000);
        },
    },
});
