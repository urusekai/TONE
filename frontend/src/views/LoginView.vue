<template>
  <main id="login-page">
    <img class="logo" src="@/assets/icons/logo-full.svg" alt="로고" />
    <form class="login-form" @submit.prevent="handleLogin">
      <div class="form-input-box">
        <span><img src="@/assets/icons/id.svg" alt="아이디" /></span>
        <input type="text" name="id" placeholder="아이디를 입력하세요" required />
      </div>
      <div class="form-input-box">
        <span><img src="@/assets/icons/password.svg" alt="비밀번호" /></span>
        <input type="password" name="pw" placeholder="비밀번호를 입력하세요" required />
      </div>
      <div class="actions">
        <span>
          <input type="checkbox" name="id_save" id="id-save" />
          <label for="id-save">아이디 저장</label>
        </span>
        <router-link to="/register">회원가입</router-link>
      </div>
      <button type="submit" class="form-submit-box">로그인</button>
    </form>
    <div class="oauth">
      <p>SNS 계정으로 로그인</p>
      <div class="oauth-inner">
        <span><img src="@/assets/icons/kakao.svg" alt="카카오로그인" /></span>
        <span><img src="@/assets/icons/facebook.svg" alt="페이스북로그인" /></span>
        <span><img src="@/assets/icons/google.svg" alt="구글로그인" /></span>
      </div>
    </div>
  </main>
</template>

<script setup>
import { useRouter } from 'vue-router';

const router = useRouter();

function handleLogin() {
  router.push('/main');
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
</style>
