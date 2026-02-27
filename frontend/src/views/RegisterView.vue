<template>
  <main id="register-page">
    <img class="logo" src="@/assets/icons/logo-full.svg" alt="로고" />

    <form class="register-form" @submit.prevent="handleRegister">
      <div class="form-input-box">
        <span><img src="@/assets/icons/id.svg" alt="아이디" /></span>
        <input v-model="form.id" type="text" placeholder="아이디를 입력하세요" required />
        <button type="button" class="id-check" :disabled="isCheckingDuplicate" @click="handleDuplicate">
          {{ isCheckingDuplicate ? '확인중...' : '중복확인' }}
        </button>
      </div>

      <div class="form-input-box">
        <span><img src="@/assets/icons/id.svg" alt="이메일" /></span>
        <input v-model="form.email" type="email" placeholder="이메일을 입력하세요" required />
      </div>

      <div class="form-input-box">
        <span><img src="@/assets/icons/password.svg" alt="비밀번호" /></span>
        <input v-model="form.pw" type="password" placeholder="비밀번호를 입력하세요" required />
      </div>

      <div class="form-input-box">
        <span><img src="@/assets/icons/password.svg" alt="비밀번호확인" /></span>
        <input
          v-model="form.pwConfirm"
          type="password"
          placeholder="비밀번호를 다시 입력하세요"
          required
        />
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

      <button type="submit" class="form-submit-box">회원가입</button>
    </form>

    <DuplicateModal
      :open="isDuplicateModalOpen"
      :checked-id="lastCheckedId"
      :available="isIdAvailable"
      @close="closeDuplicateModal"
    />
  </main>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import DuplicateModal from '@/components/DuplicateModal.vue';
import { checkDuplicateId } from '@/services/authService';

const router = useRouter();

const form = reactive({
  id: '',
  email: '',
  pw: '',
  pwConfirm: '',
  nickname: ''
});

const isCheckingDuplicate = ref(false);
const isDuplicateModalOpen = ref(false);
const lastCheckedId = ref('');
const isIdAvailable = ref(false);

function handleRegister() {
  const currentId = form.id.trim();

  if (!currentId) {
    window.alert('아이디를 입력해주세요.');
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

  // TODO: 백엔드 연결 시 이 위치에서 회원가입 API 요청 후 이동 처리
  router.push('/main');
}

async function handleDuplicate() {
  const currentId = form.id.trim();

  if (!currentId) {
    window.alert('아이디를 먼저 입력해주세요.');
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
</style>
