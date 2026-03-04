<script setup>
import { computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { usePlayerStore } from '@/stores/player';
import { useAuthStore } from '@/stores/auth';
import { fetchMyProfile } from '@/services/userService';

import BottomNav from '@/components/BottomNav.vue';
import TabMenu from '@/components/TabMenu.vue';
import Header from '@/components/Header.vue';
import MiniPlayer from '@/components/MiniPlayer.vue';
import MiniPlayerDock from '@/components/MiniPlayerDock.vue';
import MainPlayer from '@/components/MainPlayer.vue';

const route = useRoute();
const player = usePlayerStore();
const authStore = useAuthStore();

const hasTabs = computed(() => route.meta.hasTabs === true);

onMounted(async () => {
  try {
    await authStore.syncMyProfile(fetchMyProfile);
  } catch {
    // 세션이 없는 경우는 무시
  }
});
</script>

<template>
  <div class="app" :class="{ 'has-tabs': hasTabs, 'has-mini': player.isMini }">
    <Header />
    <TabMenu v-if="hasTabs" />
    <RouterView />

    <MiniPlayer v-if="player.isMini" />
    <MiniPlayerDock v-else-if="player.isHidden" />
    <MainPlayer />
    <BottomNav />
  </div>
</template>
