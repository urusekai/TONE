<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import BottomNav from '@/components/BottomNav.vue';
import TabMenu from '@/components/TabMenu.vue';
import Header from '@/components/Header.vue';

const route = useRoute();
const hasTabs = computed(() => route.meta.hasTabs === true);
const hasMini = computed(() => route.meta.hasMini === true);
</script>

<template>
  <div class="app" :class="{ 'has-tabs': hasTabs, 'has-mini': hasMini }">
    <Header />
    <TabMenu v-if="hasTabs" />
    <RouterView />
    <BottomNav />
  </div>
</template>
