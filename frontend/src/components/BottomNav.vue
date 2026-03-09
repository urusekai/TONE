<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const historyBottomPath = ref('/main');

const bottomTabPaths = new Set(['/main', '/search', '/calendar', '/my-page']);

const homeTabPaths = new Set([
  '/main',
  '/color-chart',
  '/palette-log',
  '/category',
  '/category-detail'
]);

function normalizeBottomPath(path) {
  if (homeTabPaths.has(path)) return '/main';
  if (bottomTabPaths.has(path)) return path;
  return '';
}

function readHistoryBottomPath() {
  if (typeof window === 'undefined') return '/main';
  const raw = window.history.state?.fromBottomTab || '';
  const normalized = normalizeBottomPath(String(raw));
  return normalized || '/main';
}

watch(
  () => route.fullPath,
  () => {
    if (!normalizeBottomPath(route.path)) {
      historyBottomPath.value = readHistoryBottomPath();
    }
  },
  { immediate: true }
);

const activeBottomPath = computed(() => {
  const normalized = normalizeBottomPath(route.path);
  return normalized || historyBottomPath.value;
});

const isMainActive = computed(() => activeBottomPath.value === '/main');
const isSearchActive = computed(() => activeBottomPath.value === '/search');
const isCalendarActive = computed(() => activeBottomPath.value === '/calendar');
const isMyPageActive = computed(() => activeBottomPath.value === '/my-page');
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
