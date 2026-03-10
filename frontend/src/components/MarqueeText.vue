<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
  text: {
    type: String,
    default: ''
  },
  tag: {
    type: String,
    default: 'div'
  },
  align: {
    type: String,
    default: 'left'
  },
  speed: {
    type: Number,
    default: 48
  },
  gap: {
    type: Number,
    default: 32
  }
});

const viewportRef = ref(null);
const textRef = ref(null);
const viewportWidth = ref(0);
const textWidth = ref(0);

let resizeObserver = null;

const normalizedText = computed(() => String(props.text || ''));
const isOverflowing = computed(
  () => Boolean(normalizedText.value) && textWidth.value > viewportWidth.value + 1
);
const distance = computed(() => textWidth.value + props.gap);
const duration = computed(() =>
  isOverflowing.value ? Math.max(8, distance.value / Math.max(props.speed, 1)) : 0
);
const marqueeStyle = computed(() => ({
  '--marquee-gap': `${props.gap}px`,
  '--marquee-distance': `${distance.value}px`,
  '--marquee-duration': `${duration.value}s`
}));

async function measure() {
  await nextTick();

  viewportWidth.value = viewportRef.value?.clientWidth || 0;
  textWidth.value = textRef.value?.scrollWidth || 0;
}

onMounted(() => {
  measure();

  resizeObserver = new ResizeObserver(() => {
    measure();
  });

  if (viewportRef.value) {
    resizeObserver.observe(viewportRef.value);
  }

  if (textRef.value) {
    resizeObserver.observe(textRef.value);
  }
});

onBeforeUnmount(() => {
  resizeObserver?.disconnect();
  resizeObserver = null;
});

watch(() => props.text, measure);
</script>

<template>
  <component
    :is="tag"
    ref="viewportRef"
    class="marquee-text"
    :class="[`is-${align}`, { 'is-overflowing': isOverflowing }]"
  >
    <span class="marquee-text__track" :style="marqueeStyle">
      <span ref="textRef" class="marquee-text__item">{{ normalizedText }}</span>
      <template v-if="isOverflowing">
        <span class="marquee-text__gap" aria-hidden="true"></span>
        <span class="marquee-text__item marquee-text__item--clone" aria-hidden="true">
          {{ normalizedText }}
        </span>
      </template>
    </span>
  </component>
</template>

<style scoped>
.marquee-text {
  display: block;
  width: 100%;
  min-width: 0;
  overflow: hidden;
}

.marquee-text__track {
  display: inline-flex;
  align-items: center;
  min-width: 100%;
  white-space: nowrap;
}

.marquee-text:not(.is-overflowing).is-left .marquee-text__track {
  justify-content: flex-start;
}

.marquee-text:not(.is-overflowing).is-center .marquee-text__track {
  justify-content: center;
}

.marquee-text.is-overflowing .marquee-text__track {
  min-width: max-content;
  animation: marquee-scroll var(--marquee-duration) linear infinite;
  will-change: transform;
}

.marquee-text__item,
.marquee-text__gap {
  flex: none;
}

.marquee-text__item {
  display: inline-block;
  white-space: nowrap;
}

.marquee-text__gap {
  width: var(--marquee-gap);
}

@keyframes marquee-scroll {
  0%,
  12% {
    transform: translateX(0);
  }
  88%,
  100% {
    transform: translateX(calc(var(--marquee-distance) * -1));
  }
}
</style>
