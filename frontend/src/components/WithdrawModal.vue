<script setup>
import { toRefs, watch } from 'vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  loading: { type: Boolean, default: false }
});

const emit = defineEmits(['close', 'confirm']);

const { open, loading } = toRefs(props);

// 디버그: 부모가 prop을 변경하는지 관찰
watch(open, (v) => console.log('[WithdrawModal] prop open changed:', v));
</script>

<template>
  <div v-if="open" class="modal-overlay" @click.self="emit('close')">
    <div class="modal-container" role="dialog" aria-modal="true">
      <h2 class="modal-header">회원 탈퇴</h2>

      <div class="modal-content-card">
        <p class="modal-body-text">
          정말 <span class="highlight">탈퇴</span>하시겠어요?<br />
          탈퇴하면 정보는 복구할 수 없어요.
        </p>
      </div>

      <div class="modal-actions">
        <button class="modal-confirm-btn cancel" @click="emit('close')" :disabled="loading">
          취소
        </button>
        <button class="modal-confirm-btn danger" @click="emit('confirm')" :disabled="loading">
          {{ loading ? '처리 중…' : '확인' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Overlay */
.modal-overlay {
  position: fixed;
  inset: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.65);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

/* Modal box */
.modal-container {
  background-color: #f2f2ee;
  width: 85%;
  max-width: 320px;
  padding: 25px 20px;
  border-radius: 25px;
  text-align: center;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  box-sizing: border-box;
}

.modal-header {
  font-size: 1.2rem;
  font-weight: 800;
  color: var(--modal-color-primary);
  margin-bottom: 15px;
}

.modal-content-card {
  background: #fff;
  border-radius: 17px 17px 0 0;
  padding: 25px 15px;
  margin-bottom: 20px;
  box-shadow: 0 0 2px rgba(0, 0, 0, 0.25);
}

.modal-body-text {
  font-size: 16px;
  line-height: 1.5;
  font-weight: 600;
  word-break: keep-all;
  overflow-wrap: break-word;
}

.highlight {
  color: #3f5f73;
  text-decoration: underline;
  font-weight: 700;
}

.modal-actions {
  display: flex;
  gap: 10px;
  justify-content: center;
}

.modal-confirm-btn {
  padding: 8px 14px;
  border-radius: 8px;
  border: 1px solid #ddd;
  background: #f2f2ee;
  cursor: pointer;
}

.modal-confirm-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.modal-confirm-btn.cancel {
  background: #ffffff;
  border: 1.5px solid #3f5f73;
  color: #3f5f73;
}

.modal-confirm-btn.danger {
  background: #3f5f73;
  color: #fff;
  border-color: transparent;
}
</style>
