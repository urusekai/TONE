import { createRouter, createWebHistory } from 'vue-router';

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', redirect: '/splash' },
    {
      path: '/',
      component: () => import('../layouts/AuthLayout.vue'),
      children: [
        { path: 'splash', component: () => import('../views/SplashView.vue') },
        { path: 'login', component: () => import('../views/LoginView.vue') },
        { path: 'register', component: () => import('../views/RegisterView.vue') }
      ]
    },
    {
      path: '/',
      component: () => import('../layouts/AppLayout.vue'),
      children: [
        { path: 'main', meta: { hasTabs: true }, component: () => import('../views/MainView.vue') },
        { path: 'search', component: () => import('../views/SearchView.vue') },
        { path: 'calendar', component: () => import('../views/CalendarView.vue') },
        { path: 'my-page', component: () => import('../views/MyPageView.vue') },
        { path: 'color-chart', meta: { hasTabs: true }, component: () => import('../views/ColorChartView.vue') },
        { path: 'palette-log', meta: { hasTabs: true }, component: () => import('../views/PaletteLogView.vue') },
        { path: 'category', meta: { hasTabs: true }, component: () => import('../views/CategoryView.vue') },
        { path: 'category-detail', component: () => import('../views/CategoryDetailView.vue') },
        { path: 'playlist', component: () => import('../views/PlaylistView.vue') },
        { path: 'membership', component: () => import('../views/MembershipView.vue') },
        { path: 'payment', component: () => import('../views/PaymentView.vue') },
        { path: 'profile-edit', component: () => import('../views/ProfileEditView.vue') }
      ]
    }
  ]
});

export default router;
