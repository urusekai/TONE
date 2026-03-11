<script setup>
defineProps({
  open: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  title: { type: String, default: '확인' },
  message: { type: String, default: '' },
  confirmText: { type: String, default: '확인' },
  cancelText: { type: String, default: '취소' },
  danger: { type: Boolean, default: false }
});

const emit = defineEmits(['close', 'confirm']);
</script>

<template>
  <div v-if="open" class="modal-overlay" @click.self="emit('close')">
    <div class="modal-container" role="dialog" aria-modal="true" :aria-label="title">
      <h2 class="modal-header">{{ title }}</h2>

      <div class="modal-content-card">
        <p class="modal-body-text">{{ message }}</p>
      </div>

      <div class="modal-actions">
        <button class="modal-confirm-btn cancel" @click="emit('close')" :disabled="loading">
          {{ cancelText }}
        </button>
        <button
          class="modal-confirm-btn"
          :class="{ danger }"
          @click="emit('confirm')"
          :disabled="loading"
        >
          {{ loading ? '처리 중…' : confirmText }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
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
  color: #333;
  margin: 0;
  white-space: pre-line;
  word-break: keep-all;
  overflow-wrap: break-word;
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
