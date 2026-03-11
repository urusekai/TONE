<script setup>
import { onBeforeUnmount, watch } from 'vue';

const props = defineProps({
  open: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: '알림'
  },
  message: {
    type: String,
    default: ''
  },
  confirmText: {
    type: String,
    default: '확인'
  }
});

const emit = defineEmits(['close']);

function handleKeydown(event) {
  if (event.key !== 'Escape' || !props.open) return;
  emit('close');
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      window.addEventListener('keydown', handleKeydown);
      return;
    }

    window.removeEventListener('keydown', handleKeydown);
  },
  { immediate: true }
);

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
  <div v-if="open" class="modal-overlay active" @click.self="$emit('close')">
    <div class="modal-container" role="alertdialog" aria-modal="true" :aria-label="title">
      <h2 class="modal-header">{{ title }}</h2>

      <div class="modal-content-card">
        <p class="modal-body-text">{{ message }}</p>
      </div>

      <button type="button" class="modal-confirm-btn" @click="$emit('close')">
        {{ confirmText }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.modal-body-text {
  white-space: pre-line;
}
</style>
