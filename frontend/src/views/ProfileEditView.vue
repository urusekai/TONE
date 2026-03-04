<script setup>
import { ref, reactive } from 'vue';
import ProfileModal from '@/components/ProfileModal.vue';

/** 모달 열림 상태 */
const isProfileModalOpen = ref(false);

/** 최종적으로 확정되어 저장될 폼 데이터 */
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
    window.alert('프로필 색상을 선택해주세요.');
    return;
  }

  form.profileColor = color;
  closeProfileModal();
}

function handleSubmit(e) {
  e.preventDefault();

  // 필요한 유효성 검사 예시
  if (form.password !== form.passwordConfirm) {
    window.alert('비밀번호가 일치하지 않습니다.');
    return;
  }

  // 여기서 API 호출로 프로필 수정 요청 보내면 됨
  // await updateProfile(form)
  console.log('submit payload:', { ...form });
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
    <form class="register-form profile-form" method="post" @submit="handleSubmit">
      <div class="form-input-box">
        <span><img src="@/assets/icons/id.svg" alt="이메일" /></span>
        <input
          id="profile-email"
          name="email"
          type="email"
          placeholder="이메일을 입력하세요"
          autocomplete="email"
          required
          v-model="form.email"
        />
      </div>

      <div class="form-input-box">
        <span><img src="@/assets/icons/password.svg" alt="비밀번호" /></span>
        <input
          id="profile-password"
          name="password"
          type="password"
          placeholder="비밀번호를 입력하세요"
          autocomplete="new-password"
          required
          v-model="form.password"
        />
      </div>

      <div class="form-input-box">
        <span><img src="@/assets/icons/password.svg" alt="비밀번호 확인" /></span>
        <input
          id="profile-password-confirm"
          name="passwordConfirm"
          type="password"
          placeholder="비밀번호를 다시 입력하세요"
          autocomplete="new-password"
          required
          v-model="form.passwordConfirm"
        />
      </div>

      <div class="form-input-box">
        <span><img src="@/assets/icons/id.svg" alt="닉네임" /></span>
        <input
          id="profile-nickname"
          name="nickname"
          type="text"
          placeholder="닉네임을 2 ~ 5자 내에 입력하세요"
          autocomplete="nickname"
          minlength="2"
          maxlength="5"
          required
          v-model="form.nickname"
        />
      </div>

      <button type="submit" class="form-submit-box">수정 완료</button>
    </form>

    <ProfileModal
      :open="isProfileModalOpen"
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
  margin-top: auto;
}
</style>
