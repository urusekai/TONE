<script setup>
import { onMounted, ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import WithdrawModal from '@/components/WithdrawModal.vue';
import { logoutUser } from '@/services/authService';
import { fetchMyProfile, withdrawMyAccount } from '@/services/userService';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

/* -----------------------
   사용자 데이터
------------------------ */
const user = reactive({
  name: 'Toner 님',
  email: 'toner1234_user@gmail.com',
  plan: '프로',
  profileColor: ''
});

const appVersion = '1.2.0';

/* -----------------------
   메뉴 데이터
------------------------ */
const menuSections = [
  {
    title: '앱 설정',
    items: [
      { label: '비밀번호 잠금', value: 'OFF' },
      { label: '알림 설정', value: 'ON' },
      { label: '재생 환경 설정', route: '/play-setting' }
    ]
  },
  {
    title: '서비스',
    items: [
      { label: '결제내역', route: '/payment' },
      { label: '적립내역', route: '/points' },
      { label: '보관함 관리', route: '/library' }
    ]
  },
  {
    title: '고객지원',
    items: [
      { label: '공지사항', route: '/notice' },
      { label: '고객센터', route: '/support' },
      { label: '서비스 이용약관', route: '/terms' },
      { label: '고객의 소리', route: '/feedback' },
      { label: '버전 정보', value: `v ${appVersion}` }
    ]
  }
];

/* -----------------------
   네비게이션
------------------------ */
const goTo = (route) => {
  if (route) router.push(route);
};

function applyUser(userData) {
  if (!userData) return;

  const nickname = typeof userData.nickname === 'string' ? userData.nickname.trim() : '';
  user.name = nickname ? `${nickname} 님` : 'Toner 님';
  user.email = typeof userData.email === 'string' ? userData.email : user.email;
  user.profileColor =
    typeof userData.profileColor === 'string' ? userData.profileColor : user.profileColor;
}

onMounted(async () => {
  if (authStore.currentUser) {
    applyUser(authStore.currentUser);
  }

  try {
    await authStore.syncMyProfile(fetchMyProfile);
    if (!authStore.currentUser) return;
    applyUser(authStore.currentUser);
  } catch {
    // 세션이 없거나 요청 실패 시 기존 화면 데이터 유지
  }
});

/* -----------------------
   로그아웃 / 탈퇴 공통 처리
------------------------ */
const clearAuthAndGoLogin = () => {
  authStore.clearCurrentUser();
  sessionStorage.clear();

  router.replace('/login'); // 뒤로가기 방지
};

const logout = async () => {
  try {
    await logoutUser();
  } catch {
    // 서버 로그아웃 실패 시에도 프론트 상태는 정리
  } finally {
    clearAuthAndGoLogin();
  }
};

/* -----------------------
   회원 탈퇴 모달 제어
------------------------ */
const isWithdrawOpen = ref(false);
const isWithdrawing = ref(false);

const openWithdraw = () => {
  isWithdrawOpen.value = true;
};

const closeWithdraw = () => {
  if (isWithdrawing.value) return;
  isWithdrawOpen.value = false;
};

const confirmWithdraw = async () => {
  try {
    isWithdrawing.value = true;

    await withdrawMyAccount();

    isWithdrawOpen.value = false;
    window.alert('회원 탈퇴가 완료되었습니다.');
    clearAuthAndGoLogin();
  } catch (error) {
    const message = error instanceof Error ? error.message : '회원 탈퇴 중 오류가 발생했습니다.';
    window.alert(message);
  } finally {
    isWithdrawing.value = false;
  }
};
</script>

<template>
  <main id="mypage">
    <div class="mypage-inner">
      <!-- 프로필 카드 -->
      <section class="profile-card">
        <div class="profile-main">
          <div class="avatar-circle" :style="{ backgroundColor: user.profileColor || '' }"></div>

          <div>
            <div class="name-badge">
              <span class="username">{{ user.name }}</span>
              <span class="badge">{{ user.plan }}</span>
            </div>
            <p class="email">{{ user.email }}</p>
          </div>
        </div>

        <div class="profile-actions">
          <button class="action-btn" @click="goTo('/profile-edit')">프로필 수정</button>
          <button class="action-btn" @click="goTo('/membership')">이용권 관리</button>
        </div>
      </section>

      <!-- 로그아웃 -->
      <div class="logout-row">
        <button class="logout-btn" @click="logout">로그아웃</button>
      </div>

      <!-- 메뉴 -->
      <section v-for="section in menuSections" :key="section.title" class="menu-group">
        <h2 class="group-title">{{ section.title }}</h2>

        <div
          v-for="item in section.items"
          :key="item.label"
          class="menu-item"
          @click="goTo(item.route)"
        >
          <span>{{ item.label }}</span>

          <template v-if="item.route">
            <img class="menu-arrow" src="@/assets/icons/arrow-right.svg" alt="" />
          </template>

          <template v-else>
            <span>{{ item.value }}</span>
          </template>
        </div>
      </section>

      <button class="withdraw-btn" @click="openWithdraw">회원 탈퇴</button>
    </div>

    <!-- 회원 탈퇴 모달 -->
    <WithdrawModal
      :open="isWithdrawOpen"
      :loading="isWithdrawing"
      @close="closeWithdraw"
      @confirm="confirmWithdraw"
    />
  </main>
</template>

<style scoped>
/* ==============================
   My Page 전용
============================== */

/* main 기본 flex 가운데 정렬 해제 */
#mypage {
  width: 100%;
  align-items: stretch;
  /* 가운데 정렬 제거 */
  justify-content: flex-start;
  /* 위에서부터 시작 */
}

/* 내부 컨테이너 */
#mypage .mypage-inner {
  width: 100%;
}

/* 프로필 카드 */
#mypage .profile-card {
  background: #fafaf8;
  border-radius: 15px;
  padding: 15px;
  margin-bottom: 15px;

  display: flex;
  justify-content: space-between;
  align-items: flex-start;

  box-shadow: 0 0 4px rgba(0, 0, 0, 0.1);
}

#mypage .profile-main {
  display: flex;
  align-items: center;
}

#mypage .avatar-circle {
  width: 44px;
  height: 44px;
  background: var(--color-pantone-primary);
  border-radius: 999px;
  margin-right: 12px;
}

#mypage .name-badge {
  display: flex;
  align-items: center;
  gap: 10px;
}

#mypage .badge {
  background-color: var(--color-text-primary);
  color: white;
  padding: 3px 10px;
  border-radius: 999px;
}
#mypage .username {
  font-size: 18px;
  font-weight: 700;
}

#mypage .email {
  font-size: 12px;
  margin-top: 4px;
  color: var(--color-text-secondary);
}

#mypage .profile-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

#mypage .action-btn {
  display: inline-block;
  padding: 6px 14px;
  border-radius: 999px;
  border: 1px solid #ddd;
  background: #f2f2ee;
  font-size: 12px;
  color: #3f5f73;
  text-decoration: none;
  text-align: center;
}

#mypage .logout-row {
  text-align: right;
  margin-bottom: 20px;
}

#mypage .logout-btn {
  background: none;
  border: none;
  font-size: 14px;
  text-decoration: underline;
  color: var(--color-text-primary);
  padding-right: 10px;
}

/* 메뉴 */
#mypage .menu-group {
  margin-bottom: 40px;
}

#mypage .group-title {
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 10px;
}

#mypage .menu-item {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid #e5e5e5;
  font-size: 14px;
}

#mypage .menu-arrow {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

#mypage .withdraw-btn {
  font-size: 14px;
  margin-top: 10px;
  background: none;
  border: none;
  color: red;
  text-align: left;
  margin-bottom: 20px;
}
</style>
