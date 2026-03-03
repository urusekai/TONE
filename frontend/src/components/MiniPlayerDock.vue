<script setup>
import { usePlayerStore } from '@/stores/player';
import handleIcon from '@/assets/icons/handle.svg';

const player = usePlayerStore();

const OPEN_SWIPE_THRESHOLD = 50;

let startY = 0;

function handleOpenMini() {
  player.openMini();
}

function handleTouchStart(event) {
  startY = event.touches[0].clientY;
}

function handleTouchEnd(event) {
  const endY = event.changedTouches[0].clientY;
  const deltaY = startY - endY;

  if (deltaY > OPEN_SWIPE_THRESHOLD) {
    handleOpenMini();
  }
}
</script>

<template>
  <section
    class="mini-player-dock"
    role="button"
    tabindex="0"
    aria-label="미니플레이어 열기"
    @click="handleOpenMini"
    @keydown.enter.prevent="handleOpenMini"
    @keydown.space.prevent="handleOpenMini"
    @touchstart.passive="handleTouchStart"
    @touchend="handleTouchEnd"
  >
    <img class="mini-player-dock__handle" :src="handleIcon" alt="" aria-hidden="true" />
  </section>
</template>

<style scoped>
.mini-player-dock {
  position: fixed;
  left: 50%;
  transform: translateX(-50%);
  width: 64px;
  height: 22px;
  bottom: 74px;
  z-index: 1001;
  border-radius: 10px 10px 0 0;
  background: #ffffff;
  box-shadow: 0 -1px 3px rgba(0, 0, 0, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  user-select: none;
}

.mini-player-dock__handle {
  width: 24px;
  height: 6px;
}
</style>
