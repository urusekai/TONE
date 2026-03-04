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

/*
  ⚠️ NOTE

  calendar 기록 데이터를 localStorage에서 불러오는 코드

  추후 로그인 기능 + 서버 DB가 연결되면
  API 호출 방식으로 변경될 수 있음
*/
import { useCalendarStore } from '@/stores/calendarStore';

const calendarStore = useCalendarStore();

// 앱 시작 시 저장된 데이터 불러오기
calendarStore.loadFromLocalStorage();
