import '@/assets/css/reset.css';
import '@/assets/css/font.css';
import '@/assets/css/common.css';
import '@/assets/css/popup.css';

import { createApp } from 'vue';
import { createPinia } from 'pinia';

import App from './App.vue';
import router from './router';

const app = createApp(App);

app.use(createPinia());
app.use(router);

app.mount('#app');
