<template>
  <div v-if="open" id="profileModal" class="modal-overlay active" @click.self="$emit('close')">
    <div class="modal-container">
      <div
        class="profile-main-circle"
        id="mainProfileCircle"
        :style="{ backgroundColor: selectedColor || '#B7AEA6' }"
      >
        +
      </div>
      <p class="helper-text">프로필 색상을 선택해주세요</p>
      <div class="modal-content-card">
        <div class="color-picker-container">
          <button
            v-for="color in colors"
            :key="color.value"
            type="button"
            class="color-circle"
            :class="[color.className, { selected: selectedColor === color.value }]"
            @click="handleColorClick(color.value)"
          ></button>
        </div>
      </div>
      <button type="button" class="modal-confirm-btn" @click="handleConfirmClick">확인</button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  open: {
    type: Boolean,
    default: false
  },
  initialColor: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['close', 'select-color', 'confirm']);

const colors = [
  { value: '#FFB3BA', className: 'color-pink' },
  { value: '#BAE1FF', className: 'color-purple' },
  { value: '#B2E2F2', className: 'color-blue' },
  { value: '#FFDFBA', className: 'color-orange' },
  { value: '#D4F1E9', className: 'color-green' }
];

const selectedColor = ref('');

function handleColorClick(color) {
  selectedColor.value = color;
  emit('select-color', color);
}

function handleConfirmClick() {
  emit('confirm', selectedColor.value);
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      selectedColor.value = props.initialColor || '';
    }
  }
);
</script>
