<template>
  <main id="register-page">
    <img class="logo" src="@/assets/icons/logo-full.svg" alt="로고" />

    <form class="register-form" @submit.prevent="handleRegister">
      <div class="form-input-box">
        <span><img src="@/assets/icons/id.svg" alt="아이디" /></span>
        <input
          v-model="form.id"
          type="text"
          placeholder="아이디를 입력하세요"
          minlength="4"
          maxlength="20"
          pattern="[A-Za-z0-9_]{4,20}"
          autocomplete="username"
          required
        />
        <button
          type="button"
          class="id-check"
          :disabled="isCheckingDuplicate"
          @click="handleDuplicate"
        >
          {{ isCheckingDuplicate ? '확인중...' : '중복확인' }}
        </button>
      </div>

      <div class="form-input-box">
        <span><img src="@/assets/icons/id.svg" alt="이메일" /></span>
        <input
          v-model="form.email"
          type="email"
          placeholder="이메일을 입력하세요"
          autocomplete="email"
          required
        />
      </div>

      <div class="form-input-box">
        <span><img src="@/assets/icons/password.svg" alt="비밀번호" /></span>
        <input
          v-model="form.pw"
          type="password"
          placeholder="비밀번호를 입력하세요"
          minlength="8"
          autocomplete="new-password"
          required
        />
      </div>

      <div class="form-input-box">
        <span><img src="@/assets/icons/password.svg" alt="비밀번호확인" /></span>
        <input
          v-model="form.pwConfirm"
          type="password"
          placeholder="비밀번호를 다시 입력하세요"
          minlength="8"
          autocomplete="new-password"
          required
        />
      </div>

      <div class="form-input-box">
        <span><img src="@/assets/icons/id.svg" alt="닉네임" /></span>
        <input
          v-model="form.nickname"
          type="text"
          placeholder="닉네임을 2 ~ 5자 내에 입력하세요"
          minlength="2"
          maxlength="5"
          autocomplete="nickname"
          required
        />
      </div>

      <RouterLink class="form-login-box" to="/login">로그인</RouterLink>

      <button type="submit" class="form-submit-box" :disabled="isRegistering">
        {{ isRegistering ? '가입중...' : '회원가입' }}
      </button>
    </form>

    <DuplicateModal
      :open="isDuplicateModalOpen"
      :checked-id="lastCheckedId"
      :available="isIdAvailable"
      @close="closeDuplicateModal"
    />
    <ProfileModal
      :open="isProfileModalOpen"
      :initial-color="form.profileColor"
      @close="closeProfileModal"
      @confirm="handleProfileConfirm"
    />
  </main>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import DuplicateModal from '@/components/DuplicateModal.vue';
import ProfileModal from '@/components/ProfileModal.vue';
import { checkDuplicateId, registerUser } from '@/services/authService';
import { useAuthStore } from '@/stores/auth';
import { validateRegisterId, validateRegisterPayload } from '@/utils/authValidation';

const router = useRouter();
const authStore = useAuthStore();

const form = reactive({
  id: '',
  email: '',
  pw: '',
  pwConfirm: '',
  nickname: '',
  profileColor: ''
});

const isCheckingDuplicate = ref(false);
const isDuplicateModalOpen = ref(false);
const isProfileModalOpen = ref(false);
const isRegistering = ref(false);
const lastCheckedId = ref('');
const isIdAvailable = ref(false);

function handleRegister() {
  const currentId = form.id.trim();
  const payload = {
    id: currentId,
    email: form.email.trim(),
    password: form.pw,
    nickname: form.nickname.trim()
  };
  const validationMessage = validateRegisterPayload(payload, { requireProfileColor: false });

  if (validationMessage) {
    window.alert(validationMessage);
    return;
  }

  if (!lastCheckedId.value || lastCheckedId.value !== currentId) {
    window.alert('아이디 중복확인을 먼저 해주세요.');
    return;
  }

  if (!isIdAvailable.value) {
    window.alert('사용할 수 없는 아이디입니다. 다른 아이디를 입력해주세요.');
    return;
  }

  if (form.pw !== form.pwConfirm) {
    window.alert('비밀번호가 일치하지 않습니다.');
    return;
  }

  // 회원가입 버튼을 누르면 색상 선택 모달을 연다.
  isProfileModalOpen.value = true;
}

async function handleDuplicate() {
  const currentId = form.id.trim();
  const validationMessage = validateRegisterId(currentId);

  if (validationMessage) {
    window.alert(validationMessage);
    return;
  }

  isCheckingDuplicate.value = true;

  try {
    const result = await checkDuplicateId(currentId);
    lastCheckedId.value = result.id.trim();
    isIdAvailable.value = result.available;
    isDuplicateModalOpen.value = true;
  } catch {
    window.alert('중복확인 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.');
  } finally {
    isCheckingDuplicate.value = false;
  }
}

function closeDuplicateModal() {
  isDuplicateModalOpen.value = false;
}

function closeProfileModal() {
  if (isRegistering.value) return;
  isProfileModalOpen.value = false;
}

async function handleProfileConfirm(color) {
  if (isRegistering.value) return;

  form.profileColor = color;
  const payload = {
    id: form.id.trim(),
    email: form.email.trim(),
    password: form.pw,
    nickname: form.nickname.trim(),
    profileColor: form.profileColor
  };
  const validationMessage = validateRegisterPayload(payload);

  if (validationMessage) {
    window.alert(validationMessage);
    return;
  }

  isRegistering.value = true;

  try {
    const result = await registerUser(payload);
    if (result?.user) {
      authStore.setCurrentUser(result.user);
    }
    isProfileModalOpen.value = false;
    router.push('/main');
  } catch (error) {
    const message =
      error instanceof Error
        ? error.message
        : '회원가입 중 오류가 발생했습니다. 다시 시도해주세요.';
    window.alert(message);
  } finally {
    isRegistering.value = false;
  }
}
</script>

<style scoped>
#register-page {
  justify-content: center;
  align-items: center;
  gap: 70px;
}

#register-page .register-form {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

#register-page .form-input-box span,
#register-page .register-form .id-check {
  flex-shrink: 0;
}

#register-page .register-form .id-check {
  height: 25px;
  background-color: var(--color-primary);
  color: #ffffff;
  padding: 0px 5px;
  border-radius: 8px;
}

#register-page .register-form .id-check:disabled {
  opacity: 0.7;
  cursor: default;
}

#register-page .register-form button[type='submit'] {
  margin-top: 50px;
}

#register-page .register-form .form-login-box {
  margin-top: 50px;
  width: 100%;
  padding: 20px;
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: #ffffff;
  color: var(--color-primary);
  border-radius: 15px;
  font-size: 16px;
  font-weight: 700;
}

#register-page .register-form .form-login-box + .form-submit-box {
  margin-top: 10px;
}

#register-page .register-form button[type='submit']:disabled {
  opacity: 0.7;
  cursor: default;
}
</style>
