<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import ProfileModal from '@/components/ProfileModal.vue';
import { fetchMyProfile, updateMyProfile } from '@/services/userService';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { showAlert } from '@/utils/alert';
import { validateProfileUpdatePayload } from '@/utils/authValidation';

const router = useRouter();
const authStore = useAuthStore();
const toast = useToastStore();

const isProfileModalOpen = ref(false);
const isLoading = ref(false);
const isSubmitting = ref(false);

const form = reactive({
  email: '',
  password: '',
  passwordConfirm: '',
  nickname: '',
  profileColor: ''
});

function openProfileModal() {
  isProfileModalOpen.value = true;
}

function closeProfileModal() {
  isProfileModalOpen.value = false;
}

function handleProfileConfirm(color) {
  if (!color) {
    showAlert('프로필 색상을 선택해주세요.');
    return;
  }

  form.profileColor = color;
  closeProfileModal();
}

onMounted(async () => {
  isLoading.value = true;

  try {
    await authStore.syncMyProfile(fetchMyProfile);
    const user = authStore.currentUser;
    if (!user) {
      throw new Error('사용자 정보를 찾을 수 없습니다.');
    }

    form.email = user.email || '';
    form.nickname = user.nickname || '';
    form.profileColor = user.profileColor || '';
  } catch (error) {
    const message =
      error instanceof Error ? error.message : '사용자 정보를 불러오는 중 오류가 발생했습니다.';
    showAlert(message);
    router.replace('/login');
  } finally {
    isLoading.value = false;
  }
});

async function handleSubmit() {
  if (isSubmitting.value) return;

  const email = form.email.trim();
  const nickname = form.nickname.trim();
  const password = form.password;
  const passwordConfirm = form.passwordConfirm;

  if (password || passwordConfirm) {
    if (password !== passwordConfirm) {
      showAlert('비밀번호가 일치하지 않습니다.');
      return;
    }
  }

  const payload = { email, nickname, password, profileColor: form.profileColor };
  const validationMessage = validateProfileUpdatePayload(payload);

  if (validationMessage) {
    showAlert(validationMessage);
    return;
  }

  isSubmitting.value = true;

  try {
    const requestPayload = { email, nickname, profileColor: form.profileColor };
    if (password) {
      requestPayload.password = password;
    }

    const result = await updateMyProfile(requestPayload);
    if (result?.user) {
      authStore.setCurrentUser(result.user);
    }

    toast.show('프로필이 수정되었습니다');
    router.replace('/my-page');
  } catch (error) {
    const message = error instanceof Error ? error.message : '프로필 수정 중 오류가 발생했습니다.';
    showAlert(message);
  } finally {
    isSubmitting.value = false;
  }
}
</script>

<template>
  <main id="profile-page">
    <div class="profile-image-area">
      <!-- 확정된 색(form.profileColor)만 반영 -->
      <div
        class="profile-image"
        id="profileMainAvatar"
        :style="{ backgroundColor: form.profileColor || 'var(--color-pantone-primary)' }"
      ></div>

      <!-- 클릭하면 모달 열기 -->
      <button type="button" class="profile-color-btn" @click="openProfileModal">색상 변경</button>
    </div>

    <!-- v-model로 폼 상태 연결 -->
    <form class="register-form profile-form" method="post" @submit.prevent="handleSubmit">
      <div class="form-input-box">
        <span><img src="@/assets/icons/id.svg" alt="닉네임" /></span>
        <input
          id="profile-nickname"
          name="nickname"
          type="text"
          placeholder="닉네임을 2 ~ 5자 내에 입력하세요"
          autocomplete="nickname"
          v-model="form.nickname"
        />
      </div>

      <div class="form-input-box">
        <span><img src="@/assets/icons/id.svg" alt="이메일" /></span>
        <input
          id="profile-email"
          name="email"
          type="email"
          placeholder="이메일을 입력하세요"
          autocomplete="email"
          v-model="form.email"
        />
      </div>

      <div class="form-input-box">
        <span><img src="@/assets/icons/password.svg" alt="비밀번호" /></span>
        <input
          id="profile-password"
          name="password"
          type="password"
          placeholder="변경할 비밀번호"
          autocomplete="new-password"
          v-model="form.password"
        />
      </div>

      <div class="form-input-box">
        <span><img src="@/assets/icons/password.svg" alt="비밀번호 확인" /></span>
        <input
          id="profile-password-confirm"
          name="passwordConfirm"
          type="password"
          placeholder="변경할 비밀번호 확인"
          autocomplete="new-password"
          v-model="form.passwordConfirm"
        />
      </div>

      <button type="submit" class="form-submit-box" :disabled="isLoading || isSubmitting">
        {{ isLoading ? '불러오는 중...' : isSubmitting ? '수정중...' : '수정 완료' }}
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

<style scoped>
/* =========================
   PROFILE PAGE
========================= */

#profile-page {
  width: 100%;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
  gap: 80px;
  min-height: 0;
  overflow: hidden;
}

.profile-image-area {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  flex: 0 0 auto;
}

.profile-image {
  width: 96px;
  height: 96px;
  border-radius: 999px;
  background-color: var(--color-pantone-primary);
  box-shadow: 0 0 4px rgba(0, 0, 0, 0.25);
}

.profile-color-btn {
  min-height: 36px;
  background-color: var(--color-primary);
  color: #ffffff;
  padding: 0 14px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
}

#profile-page .register-form {
  width: 100%;
  flex: 1 1 auto;
  min-height: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
  overflow-y: auto;
}

#profile-page .form-input-box span {
  flex-shrink: 0;
}

#profile-page .register-form button[type='submit'] {
  margin-top: 56px;
}
</style>
