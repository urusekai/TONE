<template>
  <Header />
  <TabMenu />

  <RouterView />

  <MiniPlayer />
  <MainPlayer />
  <BottomNav />
</template>

<script setup>
import Header from '@/components/Header.vue';
import TabMenu from '@/components/TabMenu.vue';
import BottomNav from '@/components/BottomNav.vue';
import MiniPlayer from '@/components/MiniPlayer.vue';
import MainPlayer from '@/components/MainPlayer.vue';
</script>
