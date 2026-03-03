<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();

const homeTabPaths = new Set([
  '/main',
  '/color-chart',
  '/palette-log',
  '/category',
  '/category-detail'
]);

const isMainActive = computed(() => homeTabPaths.has(route.path));
const isSearchActive = computed(() => route.path === '/search');
const isCalendarActive = computed(() => route.path === '/calendar');
const isMyPageActive = computed(() => route.path === '/my-page');
</script>

<template>
  <nav class="bottom-nav">
    <RouterLink class="nav-item" :class="{ 'is-active': isMainActive }" to="/main">
      <img src="../assets/icons/main.svg" alt="메인" />
      <img src="../assets/icons/main-active.svg" alt="메인" />
    </RouterLink>

    <RouterLink class="nav-item" :class="{ 'is-active': isSearchActive }" to="/search">
      <img src="../assets/icons/search.svg" alt="검색" />
      <img src="../assets/icons/search-active.svg" alt="검색" />
    </RouterLink>

    <RouterLink class="nav-item" :class="{ 'is-active': isCalendarActive }" to="/calendar">
      <img src="../assets/icons/calendar.svg" alt="캘린더" />
      <img src="../assets/icons/calendar-active.svg" alt="캘린더" />
    </RouterLink>

    <RouterLink class="nav-item" :class="{ 'is-active': isMyPageActive }" to="/my-page">
      <img src="../assets/icons/menu.svg" alt="전체메뉴" />
      <img src="../assets/icons/menu.svg" alt="전체메뉴" />
    </RouterLink>
  </nav>
</template>
