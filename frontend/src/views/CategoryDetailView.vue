<template>
  <main id="category-detail-page" :class="{ 'is-enter-ready': isEnterReady }">
    <section class="mood-header">
      <div class="cg-grad" :style="gradStyle"></div>

      <h1 class="mood-title">{{ moodTitle }}</h1>

      <div class="mood-tags">
        <span v-for="t in moodTags" :key="t">#{{ t }}</span>
      </div>
    </section>

    <section class="color-list">
      <p v-if="isLoading" class="color-state">플레이리스트를 불러오는 중...</p>
      <p v-else-if="errorMessage" class="color-state color-state-error">{{ errorMessage }}</p>
      <p v-else-if="!colorCards.length" class="color-state">등록된 플레이리스트가 없습니다.</p>

      <PlaylistColorCard
        v-for="(card, cardIndex) in colorCards"
        :key="card.id"
        :card="card"
        class="color-card"
        :style="{ '--card-delay': `${Math.min(8, cardIndex) * 36}ms` }"
        @select="goPlaylist"
      />
    </section>
  </main>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PlaylistColorCard from '@/components/PlaylistColorCard.vue';
import { apiRequest } from '@/services/httpClient';
import { mapCategoryPlaylistCard } from '@/utils/playlistCardMapper';

const route = useRoute();
const router = useRouter();

const mood = computed(() => (route.query.mood || 'energetic').toString().toLowerCase());
const moodLabel = computed(() => String(route.query.label || formatMoodLabel(mood.value)).trim());
const moodTags = computed(() =>
  [route.query.tag1, route.query.tag2, route.query.tag3]
    .map((tag) => String(tag || '').trim())
    .filter(Boolean)
);
const gradStyle = computed(() => ({
  '--c1': String(route.query.gradC1 || '#f2f2ee'),
  '--c2': String(route.query.gradC2 || '#cfe6d6'),
  '--c3': String(route.query.gradC3 || '#b7aea6')
}));
const isLoading = ref(false);
const errorMessage = ref('');
const playlists = ref([]);
const isEnterReady = ref(false);
let latestRequestId = 0;

function formatMoodLabel(value) {
  if (!value) return 'Category';
  return value.charAt(0).toUpperCase() + value.slice(1);
}

async function fetchPlaylists() {
  const query = new URLSearchParams({ mood: mood.value });
  const result = await apiRequest(
    `/api/categories/playlists.php?${query.toString()}`,
    {},
    '플레이리스트 목록을 불러오지 못했습니다.'
  );

  const items = Array.isArray(result?.playlists) ? result.playlists : [];

  return items.map(mapCategoryPlaylistCard);
}

async function loadCategoryDetail() {
  const currentRequestId = ++latestRequestId;

  isLoading.value = true;
  errorMessage.value = '';
  playlists.value = [];
  isEnterReady.value = false;

  const playlistResult = await fetchPlaylists()
    .then((value) => ({ ok: true, value }))
    .catch((error) => ({ ok: false, error }));

  if (currentRequestId !== latestRequestId) {
    return;
  }

  if (playlistResult.ok) {
    playlists.value = playlistResult.value;
    await nextTick();
    requestAnimationFrame(() => {
      isEnterReady.value = true;
    });
  } else {
    errorMessage.value =
      playlistResult.error instanceof Error
        ? playlistResult.error.message
        : '플레이리스트 목록을 불러오지 못했습니다.';
  }

  isLoading.value = false;
}

const moodTitle = computed(() => moodLabel.value);
const colorCards = computed(() => playlists.value);

function goPlaylist(card) {
  if (!card?.id) return;

  router.push({
    path: '/playlist',
    query: { id: card.id },
    state: { fromBottomTab: '/main' }
  });
}

watch(
  mood,
  async () => {
    await loadCategoryDetail();
  },
  { immediate: true }
);
</script>

<style scoped>
#category-detail-page {
  min-height: 0;
  padding-top: 0;
  padding-left: 0;
  padding-right: 0;
}

#category-detail-page .mood-header,
#category-detail-page .color-card {
  opacity: 0;
  will-change: transform, opacity;
}

#category-detail-page .mood-header {
  transform: translateY(18px) scale(0.985);
}

#category-detail-page .color-card {
  transform: translateY(12px);
}

#category-detail-page.is-enter-ready .mood-header {
  animation: category-detail-hero-enter 360ms cubic-bezier(0.2, 0.8, 0.2, 1) both;
}

#category-detail-page.is-enter-ready .color-card {
  animation: category-detail-card-enter 320ms ease both;
  animation-delay: calc(110ms + var(--card-delay, 0ms));
}

.mood-header {
  position: relative;
  z-index: 1;
  flex: 0 0 auto;
  margin: 0;
  padding: 16px var(--layout-x) 20px;

  text-align: center;
  background: #ffffff;
  box-shadow: 0px 2px 6.5px rgba(0, 0, 0, 0.22);
}

#category-detail-page .mood-header {
  position: sticky;
  top: 0;
  z-index: 2;
}

.mood-header .cg-grad {
  position: absolute;
  inset: 0;
  border-radius: inherit;
  background: linear-gradient(135deg, var(--c1) 0%, var(--c2) 50%, var(--c3) 100%);
  opacity: 0.4;
  z-index: 0;
}

.mood-title {
  font-size: 40px;
  font-weight: 700;
  padding: 10px;
  padding-top: 90px;
}

.mood-tags {
  display: flex;
  justify-content: center;
  gap: 10px;
  flex-wrap: wrap;
  font-size: 13px;
  opacity: 0.8;
}

.mood-title,
.mood-tags {
  position: relative;
  z-index: 1;
}

.color-list {
  padding: 25px var(--layout-x) 0;
  display: flex;
  flex-direction: column;
  gap: 22px;
}

.color-state {
  margin: 0 auto;
  color: #3f5f73;
  font-size: 14px;
  text-align: center;
}

.color-state-error {
  color: #b42318;
}

@keyframes category-detail-hero-enter {
  from {
    opacity: 0;
    transform: translateY(18px) scale(0.985);
  }

  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes category-detail-card-enter {
  from {
    opacity: 0;
    transform: translateY(12px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
