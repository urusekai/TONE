import '@/assets/css/reset.css';
import '@/assets/css/font.css';
import '@/assets/css/common.css';
import '@/assets/css/popup.css';

import { createApp } from 'vue';
import { createPinia } from 'pinia';

import App from './App.vue';
import router from './router';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

app.mount('#app');
