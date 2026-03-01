<script setup>
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';
import BottomNav from '@/components/BottomNav.vue';
import TabMenu from '@/components/TabMenu.vue';
import Header from '@/components/Header.vue';
import MiniPlayer from '@/components/MiniPlayer.vue';
import MainPlayer from '@/components/MainPlayer.vue';

const route = useRoute();
const hasTabs = computed(() => route.meta.hasTabs === true);
const isMiniVisible = ref(true);
const isMainVisible = ref(false);
</script>

<template>
  <div class="app" :class="{ 'has-tabs': hasTabs, 'has-mini': isMiniVisible }">
    <Header />
    <TabMenu v-if="hasTabs" />
    <RouterView />
    <MiniPlayer
      v-if="isMiniVisible"
      @close="isMiniVisible = false"
      @open-main-player="isMainVisible = true"
    />
    <MainPlayer :open="isMainVisible" @close="isMainVisible = false" />
    <BottomNav />
  </div>
</template>
