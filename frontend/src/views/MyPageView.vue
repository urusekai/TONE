<script setup>
import { onMounted, ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import ConfirmModal from '@/components/ConfirmModal.vue';
import WithdrawModal from '@/components/WithdrawModal.vue';
import { logoutUser } from '@/services/authService';
import { withdrawMyAccount } from '@/services/userService';
import { useAuthStore } from '@/stores/auth';
import { resetAllUserState } from '@/stores/resetAllUserState';
import { useToastStore } from '@/stores/toast';
import { showAlert } from '@/utils/alert';

const router = useRouter();
const authStore = useAuthStore();
const toast = useToastStore();

/* -----------------------
   사용자 데이터
------------------------ */
const user = reactive({
  name: 'Toner 님',
  email: 'toner1234_user@gmail.com',
  plan: '무료',
  profileColor: ''
});

const appVersion = '1.2.0';

/* -----------------------
   메뉴 데이터
------------------------ */
const menuSections = reactive([
  {
    title: '앱 설정',
    items: [
      { label: '비밀번호 잠금', value: 'OFF', toggle: true },
      { label: '알림 설정', value: 'ON', toggle: true },
      { label: '데이터 절약', value: 'ON', toggle: true }
    ]
  },
  {
    title: '서비스',
    items: [
      { label: '결제내역', route: '/payment/detail' },
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
]);

/* -----------------------
   네비게이션
------------------------ */
const goTo = (route) => {
  if (route) router.push(route);
};

const openPendingFeatureAlert = () => {
  toast.show('준비 중인 기능입니다');
};

const toggleMenuItem = (item) => {
  if (!item?.toggle) return;

  item.value = item.value === 'ON' ? 'OFF' : 'ON';

  if (item.label === '비밀번호 잠금') {
    toast.show(
      item.value === 'ON' ? '비밀번호 잠금이 적용되었습니다' : '비밀번호 잠금이 해제되었습니다'
    );
    return;
  }

  if (item.label === '알림 설정') {
    toast.show(item.value === 'ON' ? '알림이 설정되었습니다' : '알림이 해제되었습니다');
    return;
  }

  if (item.label === '데이터 절약') {
    toast.show(
      item.value === 'ON' ? '데이터 절약이 적용되었습니다' : '데이터 절약이 해제되었습니다'
    );
  }
};

function formatMembershipPlan(plan) {
  if (plan === 'pro') return '프로';
  if (plan === 'basic') return '베이직';
  return '무료';
}

function applyUser(userData) {
  if (!userData) return;

  const nickname = typeof userData.nickname === 'string' ? userData.nickname.trim() : '';
  user.name = nickname ? `${nickname} 님` : 'Toner 님';
  user.email = typeof userData.email === 'string' ? userData.email : user.email;
  user.plan = formatMembershipPlan(userData.membershipPlan);
  user.profileColor =
    typeof userData.profileColor === 'string' ? userData.profileColor : user.profileColor;
}

onMounted(() => {
  if (authStore.currentUser) {
    applyUser(authStore.currentUser);
  }
});

/* -----------------------
   로그아웃 / 탈퇴 공통 처리
------------------------ */
const clearAuthAndGoLogin = () => {
  resetAllUserState();
  router.replace('/login'); // 뒤로가기 방지
};

const isLogoutOpen = ref(false);
const isLoggingOut = ref(false);

const openLogout = () => {
  isLogoutOpen.value = true;
};

const closeLogout = () => {
  if (isLoggingOut.value) return;
  isLogoutOpen.value = false;
};

const confirmLogout = async () => {
  try {
    isLoggingOut.value = true;
    await logoutUser();
  } catch {
    // 서버 로그아웃 실패 시에도 프론트 상태는 정리
  } finally {
    isLogoutOpen.value = false;
    isLoggingOut.value = false;
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
    toast.show('회원 탈퇴가 완료되었습니다');
    clearAuthAndGoLogin();
  } catch (error) {
    const message = error instanceof Error ? error.message : '회원 탈퇴 중 오류가 발생했습니다.';
    showAlert(message);
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
        <button class="logout-btn" @click="openLogout">로그아웃</button>
      </div>

      <!-- 메뉴 -->
      <section v-for="section in menuSections" :key="section.title" class="menu-group">
        <h2 class="group-title">{{ section.title }}</h2>

        <template v-for="item in section.items" :key="item.label">
          <button
            v-if="item.route"
            type="button"
            class="menu-item menu-item-button"
            @click="openPendingFeatureAlert"
          >
            <span>{{ item.label }}</span>
            <span class="menu-arrow-button" aria-hidden="true">
              <img class="menu-arrow" src="@/assets/icons/arrow-right.svg" alt="" />
            </span>
          </button>

          <button
            v-else-if="item.toggle"
            type="button"
            class="menu-item menu-item-button"
            @click="toggleMenuItem(item)"
          >
            <span>{{ item.label }}</span>
            <span class="menu-value-button">
              {{ item.value }}
            </span>
          </button>

          <div v-else class="menu-item">
            <span>{{ item.label }}</span>
            <span>{{ item.value }}</span>
          </div>
        </template>
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
    <ConfirmModal
      :open="isLogoutOpen"
      :loading="isLoggingOut"
      title="로그아웃"
      message="정말 로그아웃하시겠어요?"
      confirm-text="로그아웃"
      danger
      @close="closeLogout"
      @confirm="confirmLogout"
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
  align-items: center;
  width: 100%;
  padding: 12px 0;
  border-bottom: 1px solid #e5e5e5;
  font-size: 14px;
  text-align: left;
  color: var(--color-text-primary);
}

#mypage .menu-item-button {
  background: none;
  border-left: none;
  border-right: none;
  border-top: none;
  border-bottom: 1px solid #e5e5e5;
  color: inherit;
  font-family: inherit;
  font-size: 14px;
  font-weight: 400;
  line-height: 1.2;
  text-align: inherit;
  appearance: none;
  cursor: pointer;
}

#mypage .menu-arrow {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

#mypage .menu-arrow-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}

#mypage .menu-value-button {
  padding: 0;
  color: inherit;
  font: inherit;
}

#mypage .withdraw-btn {
  font-size: 14px;
  margin-top: 10px;
  background: none;
  border: none;
  color: red;
  text-align: right;
  display: block;
  margin-left: auto;
  margin-right: 0;
  text-decoration: underline;
}
</style>
