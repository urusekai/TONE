<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { usePlayerStore } from '@/stores/player';

import BottomNav from '@/components/BottomNav.vue';
import TabMenu from '@/components/TabMenu.vue';
import Header from '@/components/Header.vue';
import MiniPlayer from '@/components/MiniPlayer.vue';
import MainPlayer from '@/components/MainPlayer.vue';

const route = useRoute();
const player = usePlayerStore();
const appRef = ref(null);

const hasTabs = computed(() => route.meta.hasTabs === true);

const OPEN_SWIPE_THRESHOLD = 70;
const BOTTOM_EDGE_TRIGGER = 90;

let startY = 0;
let isSwipingUpToOpen = false;

function handleAppTouchStart(event) {
  if (!player.isHidden) return;

  const touchY = event.touches[0].clientY;
  const viewportHeight = window.innerHeight;
  const isBottomEdge = viewportHeight - touchY <= BOTTOM_EDGE_TRIGGER;

  if (!isBottomEdge) return;

  startY = touchY;
  isSwipingUpToOpen = true;
}

function handleAppTouchEnd(event) {
  if (!isSwipingUpToOpen) return;
  isSwipingUpToOpen = false;

  const endY = event.changedTouches[0].clientY;
  const deltaY = startY - endY;

  if (deltaY > OPEN_SWIPE_THRESHOLD) {
    player.openMini();
  }
}

onMounted(() => {
  const appEl = appRef.value;
  if (!appEl) return;

  appEl.addEventListener('touchstart', handleAppTouchStart, { passive: true });
  appEl.addEventListener('touchend', handleAppTouchEnd, { passive: true });
});

onBeforeUnmount(() => {
  const appEl = appRef.value;
  if (!appEl) return;

  appEl.removeEventListener('touchstart', handleAppTouchStart);
  appEl.removeEventListener('touchend', handleAppTouchEnd);
});
</script>

<template>
  <div ref="appRef" class="app" :class="{ 'has-tabs': hasTabs, 'has-mini': player.isMini }">
    <Header />
    <TabMenu v-if="hasTabs" />
    <RouterView />

    <MiniPlayer v-if="player.isMini" />
    <MainPlayer />
    <BottomNav />
  </div>
</template>
