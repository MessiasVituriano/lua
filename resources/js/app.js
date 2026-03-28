import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';
import axios from 'axios';

axios.defaults.baseURL = '/api';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.withCredentials = true;

const pinia = createPinia();
const app = createApp(App);
app.use(pinia);
app.use(router);

import { useThemeStore } from './stores/theme';
useThemeStore().init();

app.mount('#app');
