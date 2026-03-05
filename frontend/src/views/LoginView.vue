<template>
  <main id="login-page">
    <img class="logo" src="@/assets/icons/logo-full.svg" alt="로고" />
    <form class="login-form" @submit.prevent="handleLogin">
      <div class="form-input-box">
        <span><img src="@/assets/icons/id.svg" alt="아이디" /></span>
        <input v-model="form.id" type="text" name="id" placeholder="아이디를 입력하세요" required />
      </div>
      <div class="form-input-box">
        <span><img src="@/assets/icons/password.svg" alt="비밀번호" /></span>
        <input
          v-model="form.pw"
          type="password"
          name="pw"
          placeholder="비밀번호를 입력하세요"
          required
        />
      </div>
      <div class="actions">
        <span>
          <input type="checkbox" name="id_save" id="id-save" />
          <label for="id-save">아이디 저장</label>
        </span>
        <router-link to="/register">회원가입</router-link>
      </div>
      <button type="submit" class="form-submit-box" :disabled="isLoggingIn">
        {{ isLoggingIn ? '로그인중...' : '로그인' }}
      </button>
    </form>
    <div class="oauth">
      <p>SNS 계정으로 로그인</p>
      <div class="oauth-inner">
        <button type="button" class="oauth-btn" @click="handleSocialLogin('kakao')">
          <img src="@/assets/icons/kakao.svg" alt="카카오로그인" />
        </button>
        <button type="button" class="oauth-btn" @click="handleSocialLogin('naver')">
          <img src="@/assets/icons/naver.svg" alt="네이버로그인" />
        </button>
        <button type="button" class="oauth-btn" @click="handleSocialLogin('google')">
          <img src="@/assets/icons/google.svg" alt="구글로그인" />
        </button>
      </div>
    </div>
  </main>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { loginUser, startSocialLogin } from '@/services/authService';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const form = reactive({
  id: '',
  pw: ''
});

const isLoggingIn = ref(false);

async function handleLogin() {
  if (isLoggingIn.value) return;

  const id = form.id.trim();
  const password = form.pw;

  if (!id || !password) {
    window.alert('아이디와 비밀번호를 입력해주세요.');
    return;
  }

  isLoggingIn.value = true;

  try {
    const result = await loginUser({ id, password });

    if (result?.user) {
      authStore.setCurrentUser(result.user);
    }

    router.push('/main');
  } catch (error) {
    const message = error instanceof Error ? error.message : '로그인 중 오류가 발생했습니다.';
    window.alert(message);
  } finally {
    isLoggingIn.value = false;
  }
}

function handleSocialLogin(provider) {
  if (provider === 'kakao' || provider === 'google' || provider === 'naver') {
    startSocialLogin(provider);
    return;
  }

  window.alert('해당 소셜 로그인은 준비 중입니다.');
}
</script>

<style scoped>
#login-page {
  justify-content: center;
  align-items: center;
  gap: 70px;
}

#login-page .login-form {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

#login-page .login-form .actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0px 10px;
  font-weight: 500;
}

/* 기본 체크박스 숨김 */
#login-page .login-form .actions input[type='checkbox'] {
  display: none;
}

#login-page .login-form .actions label {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  line-height: 1;
}

/* 체크박스 버튼 */
#login-page .login-form .actions label::before {
  content: '';
  width: 12px;
  height: 12px;
  background: url('@/assets/icons/checkbox-default.svg') no-repeat center;
  background-size: contain;
  flex: 0 0 12px; /* 아이콘 크기 고정 */
  filter: drop-shadow(0 0 2px rgba(0, 0, 0, 0.25));
}

/* 체크됐을 때 */
#login-page input[type='checkbox']:checked + label::before {
  background: url('@/assets/icons/checkbox-checked.svg') no-repeat center;
  background-size: contain;
}

#login-page .oauth {
  width: 100%;
  text-align: center;
}

#login-page .oauth p {
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 600;
  font-size: 12px;
  color: #3f5f73;
  margin: 0;
}

/* 양쪽 선 */
#login-page .oauth p::before,
#login-page .oauth p::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #3f5f73;
}

#login-page .oauth .oauth-inner {
  margin-top: 20px;
  display: flex;
  justify-content: center;
  gap: 30px;
}

#login-page .oauth .oauth-btn {
  border: none;
  background: none;
  padding: 0;
  display: inline-flex;
  cursor: pointer;
}
</style>
