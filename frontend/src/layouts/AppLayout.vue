<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { usePlayerStore } from '@/stores/player';

import BottomNav from '@/components/BottomNav.vue';
import TabMenu from '@/components/TabMenu.vue';
import Header from '@/components/Header.vue';
import MiniPlayer from '@/components/MiniPlayer.vue';
import MainPlayer from '@/components/MainPlayer.vue';

const route = useRoute();
const player = usePlayerStore();

const hasTabs = computed(() => route.meta.hasTabs === true);
</script>

<template>
  <div class="app" :class="{ 'has-tabs': hasTabs, 'has-mini': player.isMini }">
    <Header />
    <TabMenu v-if="hasTabs" />
    <RouterView />

    <MiniPlayer v-if="player.isMini" />
    <MainPlayer />
    <BottomNav />
  </div>
</template>
