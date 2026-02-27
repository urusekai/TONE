<template>
  <div v-if="open" class="modal-overlay active" @click.self="$emit('close')">
    <div class="modal-container">
      <h2 class="modal-header">중복확인</h2>

      <div class="modal-content-card">
        <p class="modal-body-text">
          <span class="highlight">{{ checkedId }}</span> 아이디<br />
          {{ available ? '이용가능 합니다.' : '이미 사용 중입니다.' }}
        </p>
      </div>

      <button type="button" class="modal-confirm-btn" @click="$emit('close')">확인</button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  open: Boolean,
  checkedId: {
    type: String,
    default: ''
  },
  available: {
    type: Boolean,
    default: false
  }
});

defineEmits(['close']);
</script>
