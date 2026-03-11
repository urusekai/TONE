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

      <article
        v-for="(card, cardIndex) in colorCards"
        :key="card.id"
        class="color-card"
        :style="{ '--card-delay': `${Math.min(8, cardIndex) * 36}ms` }"
        role="button"
        tabindex="0"
        @click="goPlaylist(card)"
        @keydown.enter="goPlaylist(card)"
      >
        <div class="color-bar" :style="{ background: card.color_hex }"></div>

        <div class="color-content">
          <div class="color-top">
            <h3 class="color-name">{{ card.color_name }}</h3>

            <div class="color-right">
              <span class="arrow">
                <img :src="arrowRight" alt=">" />
              </span>
            </div>
          </div>

          <div class="song-area">
            <ul class="song-list">
              <li v-for="(song, index) in card.preview_songs" :key="index">{{ song }}</li>
            </ul>
            <p class="total">총 {{ card.total_tracks }}곡</p>
          </div>
        </div>
      </article>
    </section>
  </main>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import arrowRight from '@/assets/icons/arrow-right.svg';
import { apiRequest } from '@/services/httpClient';

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

function toNumber(value, fallback = 0) {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
}

function mapPreviewSongs(songs) {
  if (!Array.isArray(songs)) {
    return [];
  }

  return songs
    .map((song) => {
      const artist = String(song?.artist || '').trim();
      const title = String(song?.title || '').trim();

      if (artist && title) return `${artist} - ${title}`;
      return title || artist;
    })
    .filter(Boolean)
    .slice(0, 3);
}

function mapCard(item) {
  return {
    id: String(item?.id || ''),
    pantone_code: String(item?.pantone_code || ''),
    color_name: String(item?.color_name || ''),
    color_hex: String(item?.color_hex || '#b7aea6'),
    preview_songs: mapPreviewSongs(item?.previewSongs),
    total_tracks: toNumber(item?.totalTracks)
  };
}

async function fetchPlaylists() {
  const query = new URLSearchParams({ mood: mood.value });
  const result = await apiRequest(
    `/api/categories/playlists.php?${query.toString()}`,
    {},
    '플레이리스트 목록을 불러오지 못했습니다.'
  );

  const items = Array.isArray(result?.playlists) ? result.playlists : [];

  return items.map(mapCard);
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

.color-card {
  flex: 0 0 auto;
  width: 100%;
  max-width: 352px;
  height: 120px;
  margin: 0 auto;

  display: flex;
  overflow: hidden;
  border-radius: 18px;

  background: #ffffff;
  box-shadow: 0 0px 4px rgba(0, 0, 0, 0.25);
  cursor: pointer;
}

.color-bar {
  width: 95px;
  height: 120px;
  flex: 0 0 95px;
}

.color-content {
  flex: 1;
  padding: 10px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.color-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  padding-bottom: 12px;
}

.color-name {
  color: #3a586a;
  font-family: 'Pretendard', sans-serif;
  font-size: 26px;
  font-weight: 700;
  line-height: 1;
  margin: 0;
}

.color-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  padding-top: 10px;
  gap: 10px;
}

.arrow {
  font-size: 20px;
  line-height: 1;
  opacity: 0.55;
}

.song-area {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 12px;
}

.song-list {
  flex: 0 0 180px;
  width: 180px;
  margin: 0;
  padding: 0;
  list-style: none;
  font-size: 14px;
  color: #b7aeac;
  line-height: 1.1;
}

.song-list li {
  margin: 0;
  position: relative;
  padding-left: 10px;
  width: 100%;
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.song-list li::before {
  content: '•';
  position: absolute;
  left: 0;
  top: 0;
}

.total {
  flex: 0 0 auto;
  margin: 0;
  font-size: 10px;
  color: #b7aeac;
  white-space: nowrap;
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
