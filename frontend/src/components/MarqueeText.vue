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
  },
  pause: {
    type: Number,
    default: 1
  }
});

const viewportRef = ref(null);
const trackRef = ref(null);
const textRef = ref(null);
const viewportWidth = ref(0);
const textWidth = ref(0);

let resizeObserver = null;
let marqueeAnimation = null;

const normalizedText = computed(() => String(props.text || ''));
const isOverflowing = computed(
  () => Boolean(normalizedText.value) && textWidth.value > viewportWidth.value + 1
);
const distance = computed(() => textWidth.value + props.gap);
const moveDuration = computed(() =>
  isOverflowing.value ? Math.max(0, distance.value / Math.max(props.speed, 1)) : 0
);
const duration = computed(() =>
  isOverflowing.value ? Math.max(moveDuration.value + props.pause, props.pause) : 0
);
const marqueeStyle = computed(() => ({
  '--marquee-gap': `${props.gap}px`
}));

async function measure() {
  await nextTick();

  viewportWidth.value = viewportRef.value?.clientWidth || 0;
  textWidth.value = textRef.value?.scrollWidth || 0;
}

function stopAnimation() {
  marqueeAnimation?.cancel();
  marqueeAnimation = null;
}

function restartAnimation() {
  stopAnimation();

  if (!isOverflowing.value || !trackRef.value) return;

  const holdRatio = duration.value > 0 ? props.pause / duration.value : 0;

  marqueeAnimation = trackRef.value.animate(
    [
      { transform: 'translateX(0)', offset: 0 },
      { transform: 'translateX(0)', offset: holdRatio },
      { transform: `translateX(${distance.value * -1}px)`, offset: 1 }
    ],
    {
      duration: duration.value * 1000,
      iterations: Number.POSITIVE_INFINITY,
      easing: 'linear'
    }
  );
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
  stopAnimation();
  resizeObserver?.disconnect();
  resizeObserver = null;
});

watch(() => props.text, measure);
watch([isOverflowing, distance, duration, () => props.pause], restartAnimation);
</script>

<template>
  <component
    :is="tag"
    ref="viewportRef"
    class="marquee-text"
    :class="[`is-${align}`, { 'is-overflowing': isOverflowing }]"
  >
    <span ref="trackRef" class="marquee-text__track" :style="marqueeStyle">
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
</style>
