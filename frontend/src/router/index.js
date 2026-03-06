import { createRouter, createWebHistory } from 'vue-router';
import { buildApiUrl } from '@/services/httpClient';

const AUTH_STORAGE_KEY = 'tone_current_user';

function hasStoredSession() {
  try {
    const raw = localStorage.getItem(AUTH_STORAGE_KEY);
    if (!raw) return false;

    const parsed = JSON.parse(raw);
    return Boolean(parsed && typeof parsed === 'object');
  } catch {
    return false;
  }
}

async function hasServerSession() {
  const response = await fetch(buildApiUrl('/api/auth/me.php'), {
    credentials: 'include'
  });

  return response.ok;
}

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
        { path: 'register', component: () => import('../views/RegisterView.vue') },
        { path: 'social-complete', component: () => import('../views/SocialCompleteView.vue') }
      ]
    },
    {
      path: '/',
      meta: { requiresAuth: true },
      component: () => import('../layouts/AppLayout.vue'),
      children: [
        {
          path: 'main',
          meta: { hasTabs: true, headerType: 'logo' },
          component: () => import('../views/MainView.vue')
        },
        {
          path: 'search',
          meta: { headerType: 'back-title', title: '검색', backTo: '/main' },
          component: () => import('../views/SearchView.vue')
        },
        {
          path: 'calendar',
          meta: { headerType: 'back-title', title: '캘린더', backTo: '/main' },
          component: () => import('../views/CalendarView.vue')
        },
        {
          path: 'my-page',
          meta: { headerType: 'logo-title', title: '마이페이지' },
          component: () => import('../views/MyPageView.vue')
        },
        {
          path: 'color-chart',
          meta: { hasTabs: true, headerType: 'logo' },
          component: () => import('../views/ColorChartView.vue')
        },
        {
          path: 'palette-log',
          meta: { hasTabs: true, headerType: 'logo' },
          component: () => import('../views/PaletteLogView.vue')
        },
        {
          path: 'category',
          meta: { hasTabs: true, headerType: 'logo' },
          component: () => import('../views/CategoryView.vue')
        },
        {
          path: 'category-detail',
          meta: {
            headerType: 'back-title',
            title: '카테고리 상세',
            backTo: '/category',
            headerTransparent: true,
            showMoodHeader: true
          },
          component: () => import('../views/CategoryDetailView.vue')
        },
        {
          path: 'playlist',
          meta: { headerType: 'back-title', title: '플레이리스트', backTo: '/main' },
          component: () => import('../views/PlaylistView.vue')
        },
        {
          path: 'membership',
          meta: { headerType: 'back-title', title: '이용권 관리', backTo: '/my-page' },
          component: () => import('../views/MembershipView.vue')
        },
        {
          path: 'payment',
          meta: { headerType: 'back-title', title: '결제', backTo: '/membership' },
          component: () => import('../views/PaymentView.vue')
        },
        {
          path: 'profile-edit',
          meta: { headerType: 'back-title', title: '프로필 수정', backTo: '/my-page' },
          component: () => import('../views/ProfileEditView.vue')
        }
      ]
    }
  ]
});

router.beforeEach(async (to) => {
  if (!to.matched.some((record) => record.meta.requiresAuth)) {
    return true;
  }

  if (!hasStoredSession()) {
    return {
      path: '/login',
      query: to.path === '/login' ? undefined : { redirect: to.fullPath }
    };
  }

  try {
    if (await hasServerSession()) {
      return true;
    }
  } catch {
    // 세션 확인 실패 시 로그인으로 이동
  }

  try {
    localStorage.removeItem(AUTH_STORAGE_KEY);
  } catch {
    // ignore storage failure
  }

  return {
    path: '/login',
    query: to.path === '/login' ? undefined : { redirect: to.fullPath }
  };
});

export default router;
