<script setup>
import { onBeforeUnmount, watch } from 'vue';

const props = defineProps({
  open: {
    type: Boolean,
    default: false
  },
  message: {
    type: String,
    default: ''
  },
  duration: {
    type: Number,
    default: 2000
  }
});

const emit = defineEmits(['close']);

let closeTimer = null;

function clearCloseTimer() {
  if (closeTimer) {
    window.clearTimeout(closeTimer);
    closeTimer = null;
  }
}

watch(
  () => props.open,
  (isOpen) => {
    clearCloseTimer();

    if (!isOpen) return;

    closeTimer = window.setTimeout(() => {
      emit('close');
    }, props.duration);
  }
);

onBeforeUnmount(() => {
  clearCloseTimer();
});
</script>

<template>
  <div v-if="open" class="toast-message" role="status" aria-live="polite">
    {{ message }}
  </div>
</template>

<style scoped>
.toast-message {
  position: fixed;
  bottom: 140px;
  left: 50%;
  transform: translateX(-50%);
  background: #3f5f73;
  color: #fff;
  padding: 10px 18px;
  border-radius: 20px;
  font-size: 13px;
  animation: toast-fade 2s ease forwards;
  z-index: 9999;
}

@keyframes toast-fade {
  0% {
    opacity: 0;
    transform: translate(-50%, 20px);
  }
  20% {
    opacity: 1;
    transform: translate(-50%, 0);
  }
  80% {
    opacity: 1;
  }
  100% {
    opacity: 0;
  }
}
</style>
