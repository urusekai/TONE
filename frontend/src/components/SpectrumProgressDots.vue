<template>
  <div
    class="spectrum-progress-dots"
    role="group"
    :aria-label="`질문 진행도 ${safeCurrentStep}/${safeTotal}`"
  >
    <span
      v-for="index in safeTotal"
      :key="index"
      class="spectrum-progress-dot"
      :class="{ 'is-active': index - 1 === safeCurrentIndex }"
      aria-hidden="true"
    ></span>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  total: {
    type: Number,
    default: 0
  },
  currentIndex: {
    type: Number,
    default: 0
  }
});

const safeTotal = computed(() => Math.max(0, Number(props.total) || 0));
const safeCurrentIndex = computed(() => {
  if (!safeTotal.value) {
    return 0;
  }

  const nextIndex = Number(props.currentIndex) || 0;
  return Math.min(Math.max(nextIndex, 0), safeTotal.value - 1);
});
const safeCurrentStep = computed(() => (safeTotal.value ? safeCurrentIndex.value + 1 : 0));
</script>

<style scoped>
.spectrum-progress-dots {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.spectrum-progress-dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: #d9d9d9;
  transition:
    background-color 180ms ease,
    transform 180ms ease;
}

.spectrum-progress-dot.is-active {
  background: #3f5f73;
  transform: scale(1.05);
}

@media (prefers-reduced-motion: reduce) {
  .spectrum-progress-dot {
    transition: none;
  }
}
</style>
