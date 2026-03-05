<template>
  <main id="social-complete-page">
    <img class="logo" src="@/assets/icons/logo-full.svg" alt="로고" />

    <form class="social-complete-form" @submit.prevent="handleSubmit">
      <p class="guide-text">소셜 로그인 추가 정보를 입력해주세요.</p>

      <div class="form-input-box">
        <span><img src="@/assets/icons/id.svg" alt="이메일" /></span>
        <input v-model="form.email" type="email" placeholder="이메일을 입력하세요" required />
      </div>

      <div class="form-input-box">
        <span><img src="@/assets/icons/id.svg" alt="닉네임" /></span>
        <input
          v-model="form.nickname"
          type="text"
          placeholder="닉네임을 2 ~ 5자 내에 입력하세요"
          required
        />
      </div>

      <button type="button" class="color-select-btn" @click="openProfileModal">
        {{ form.profileColor ? '프로필 색상 변경' : '프로필 색상 선택' }}
      </button>

      <button type="submit" class="form-submit-box" :disabled="isSubmitting">
        {{ isSubmitting ? '처리중...' : '완료' }}
      </button>
    </form>

    <ProfileModal
      :open="isProfileModalOpen"
      :initial-color="form.profileColor"
      @close="closeProfileModal"
      @confirm="handleProfileConfirm"
    />
  </main>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { completeSocialSignup } from '@/services/authService';
import { useAuthStore } from '@/stores/auth';
import ProfileModal from '@/components/ProfileModal.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const form = reactive({
  provider: String(route.query.provider ?? ''),
  providerId: String(route.query.providerId ?? ''),
  email: '',
  nickname: '',
  profileColor: ''
});

const isSubmitting = ref(false);
const isProfileModalOpen = ref(false);

onMounted(() => {
  if (!form.provider || !form.providerId) {
    window.alert('잘못된 접근입니다. 다시 로그인해주세요.');
    router.replace('/login');
  }
});

function openProfileModal() {
  isProfileModalOpen.value = true;
}

function closeProfileModal() {
  if (isSubmitting.value) return;
  isProfileModalOpen.value = false;
}

function handleProfileConfirm(color) {
  if (!color) {
    window.alert('프로필 색상을 선택해주세요.');
    return;
  }

  form.profileColor = color;
  isProfileModalOpen.value = false;
}

async function handleSubmit() {
  if (isSubmitting.value) return;

  const payload = {
    provider: form.provider,
    providerId: form.providerId,
    email: form.email.trim(),
    nickname: form.nickname.trim(),
    profileColor: form.profileColor
  };

  if (!payload.email || !payload.nickname || !payload.profileColor) {
    window.alert('이메일, 닉네임, 프로필 색상을 입력해주세요.');
    return;
  }

  isSubmitting.value = true;

  try {
    const result = await completeSocialSignup(payload);

    if (result?.user) {
      authStore.setCurrentUser(result.user);
    }

    router.replace('/main');
  } catch (error) {
    const message =
      error instanceof Error ? error.message : '소셜 회원가입 처리 중 오류가 발생했습니다.';
    window.alert(message);
  } finally {
    isSubmitting.value = false;
  }
}
</script>

<style scoped>
#social-complete-page {
  justify-content: center;
  align-items: center;
  gap: 70px;
}

#social-complete-page .social-complete-form {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

#social-complete-page .social-complete-form .guide-text {
  margin-bottom: 20px;
  text-align: center;
  font-size: 14px;
  color: var(--color-text-secondary);
}

#social-complete-page .social-complete-form button[type='submit'] {
  margin-top: 40px;
}

#social-complete-page .social-complete-form .color-select-btn {
  margin-top: 8px;
  width: 100%;
  min-height: 42px;
  border-radius: 12px;
  background: #ffffff;
  color: var(--color-primary);
  font-weight: 700;
}

#social-complete-page .social-complete-form button[type='submit']:disabled {
  opacity: 0.7;
  cursor: default;
}
</style>
