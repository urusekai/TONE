<script setup>
import { nextTick, onMounted, reactive, ref, watch } from 'vue';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { FreeMode } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/free-mode';
import { useRouter } from 'vue-router';
import { apiRequest } from '@/services/httpClient';

const router = useRouter();
const swiperModules = [FreeMode];

const STORAGE_KEY = 'tone_recent_tags_v1';
const MAX_TAGS = 12;

// localStorage에서 불러오기
const loadTags = () => {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    const parsed = raw ? JSON.parse(raw) : null;
    return Array.isArray(parsed) ? parsed : ['Coral', 'White', '카더가든', '파란색', '여름노래'];
  } catch {
    return ['Coral', 'White', '카더가든', '파란색', '여름노래'];
  }
};

const searchData = reactive({
  tags: loadTags(),
  recentColors: [],
  recommended: []
});

const keyword = ref('');
const isLoadingColors = ref(false);
const isEnterReady = ref(false);

// tags가 바뀔 때마다 localStorage 저장
watch(
  () => searchData.tags,
  (newTags) => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(newTags));
  },
  { deep: true }
);

function normalizeTag(text) {
  return text.trim().replace(/\s+/g, ' ');
}

function addRecentTag(text) {
  const t = normalizeTag(text);
  if (!t) return;

  // 이미 있으면 맨 앞으로 올리기
  const existIdx = searchData.tags.findIndex((x) => x.toLowerCase() === t.toLowerCase());
  if (existIdx !== -1) searchData.tags.splice(existIdx, 1);

  // 맨 앞에 추가
  searchData.tags.unshift(t);

  // 최대 개수 제한
  if (searchData.tags.length > MAX_TAGS) searchData.tags.length = MAX_TAGS;
}

function removeTag(tag) {
  const idx = searchData.tags.indexOf(tag);
  if (idx !== -1) searchData.tags.splice(idx, 1);
}

function goToPlaylist(payload) {
  const playlistId = String(payload?.id || '').trim();
  if (!playlistId) return;
  router.push({
    path: '/playlist',
    query: { id: playlistId },
    state: { fromBottomTab: '/search' }
  });
}

function onSubmit(e) {
  e.preventDefault();

  // 양쪽 공백 제거
  const t = keyword.value.trim();
  if (!t) return; // 빈 문자열이면 무시

  searchData.tags.unshift(t); // 맨 앞에 추가
  keyword.value = ''; // 입력창 초기화
}

function mapRecentColor(item) {
  const playlist = item?.playlist ?? {};
  return {
    id: String(playlist?.id || ''),
    name: String(playlist?.color_name || ''),
    code: String(playlist?.color_hex || '#B7AEA6')
  };
}

function mapRecommendedColor(item) {
  return {
    id: String(item?.id || ''),
    brand: 'PANTONE',
    name: String(item?.color_name || ''),
    code: String(item?.color_hex || '#B7AEA6')
  };
}

async function loadSearchCollections() {
  isLoadingColors.value = true;
  isEnterReady.value = false;

  try {
    const [recentResult, chartResult] = await Promise.all([
      apiRequest('/api/palette-logs/list.php', {}, '최근 컬러를 불러오지 못했습니다.'),
      apiRequest('/api/playlist/chart.php', {}, '인기 컬러를 불러오지 못했습니다.')
    ]);

    const paletteLogs = Array.isArray(recentResult?.paletteLogs) ? recentResult.paletteLogs : [];
    const chartPlaylists = Array.isArray(chartResult?.playlists) ? chartResult.playlists : [];

    const recentUnique = paletteLogs.filter(
      (item, index, array) =>
        array.findIndex((target) => String(target?.playlist?.id || '') === String(item?.playlist?.id || '')) === index
    );

    searchData.recentColors = recentUnique.slice(0, 8).map(mapRecentColor);
    searchData.recommended = chartPlaylists.slice(0, 6).map(mapRecommendedColor);
    await nextTick();
    requestAnimationFrame(() => {
      isEnterReady.value = true;
    });
  } catch {
    searchData.recentColors = [];
    searchData.recommended = [];
  } finally {
    isLoadingColors.value = false;
  }
}

onMounted(async () => {
  await loadSearchCollections();
});
</script>

<template>
  <main id="search" :class="{ 'is-enter-ready': isEnterReady }">
    <section class="search-bar-section" style="--search-section-delay: 0ms">
      <form class="search-input-box" role="search" @submit="onSubmit">
        <input v-model="keyword" type="text" placeholder="노래, 아티스트, 색상 검색" />
        <button type="submit" class="btn-search" aria-label="검색">
          <img src="@/assets/icons/search.svg" alt="" class="icon-search" />
        </button>
      </form>
    </section>

    <div class="search-content-scroll">
      <!-- 최근 검색어 -->
      <section class="search-section" style="--search-section-delay: 40ms">
        <h3 class="section-title">최근 검색어</h3>

        <Swiper
          class="search-swiper tag-swiper"
          :modules="swiperModules"
          :slides-per-view="'auto'"
          :space-between="14"
          :free-mode="{ enabled: true, momentumBounce: false }"
          :grab-cursor="true"
          :resistance-ratio="0"
          :watch-overflow="true"
        >
          <SwiperSlide v-for="t in searchData.tags" :key="t" class="tag-slide">
            <div class="tag">
              {{ t }}
              <button class="btn-delete" type="button" @click.stop="removeTag(t)">
                <img src="@/assets/icons/remove.svg" alt="삭제" />
              </button>
            </div>
          </SwiperSlide>
        </Swiper>
      </section>

      <!-- 최근 컬러 -->
      <section class="recent-colors-section" style="--search-section-delay: 80ms">
        <h3 class="section-title">최근 컬러</h3>

        <div class="recent-colors-wrapper">
          <p v-if="isLoadingColors" class="section-empty recent-colors-empty">불러오는 중...</p>
          <p v-else-if="searchData.recentColors.length === 0" class="section-empty recent-colors-empty">
            최근 저장한 컬러가 없습니다.
          </p>
          <Swiper
            v-else
            class="search-swiper recent-colors-swiper"
            :modules="swiperModules"
            :slides-per-view="'auto'"
            :space-between="14"
            :free-mode="{ enabled: true, momentumBounce: false }"
            :grab-cursor="true"
            :resistance-ratio="0"
            :watch-overflow="true"
          >
            <SwiperSlide v-for="c in searchData.recentColors" :key="c.name" class="color-slide">
              <div class="color-item" @click="goToPlaylist(c)">
                <div class="color-circle" :style="{ backgroundColor: c.code }"></div>
                <span class="color-label">{{ c.name }}</span>
              </div>
            </SwiperSlide>
          </Swiper>
        </div>
      </section>

      <!-- 인기 추천 컬러 -->
      <section class="recommended-colors-section" style="--search-section-delay: 120ms">
        <h3 class="section-title">인기 추천 컬러</h3>

        <p v-if="isLoadingColors" class="section-empty">불러오는 중...</p>
        <p v-else-if="searchData.recommended.length === 0" class="section-empty">
          표시할 인기 컬러가 없습니다.
        </p>
        <div class="color-card-grid" id="color-card-grid">
          <article
            v-for="r in searchData.recommended"
            :key="`${r.brand}-${r.name}`"
            class="pantone-card"
            @click="goToPlaylist(r)"
          >
            <div class="card-color-top" :style="{ backgroundColor: r.code }"></div>
            <div class="card-info-bottom">
              <span class="brand-name">{{ r.brand }}</span>
              <p class="color-detail-name">{{ r.name }}</p>
            </div>
          </article>
        </div>
      </section>
    </div>
  </main>
</template>

<style scoped>
/* 1. 기본 배경 및 텍스트 설정 */
#search {
  --section-gap: 30px;
  --title-content-gap: 12px;

  justify-content: flex-start;
  align-items: stretch;
  min-height: 0;
  overflow: hidden;
  padding-bottom: 0;
  background-color: #f2f2ee;
  box-sizing: border-box;
}

#search .search-bar-section,
#search .search-section,
#search .recent-colors-section,
#search .recommended-colors-section {
  opacity: 0;
  transform: translateY(12px);
  will-change: transform, opacity;
}

#search.is-enter-ready .search-bar-section,
#search.is-enter-ready .search-section,
#search.is-enter-ready .recent-colors-section,
#search.is-enter-ready .recommended-colors-section {
  animation: search-section-enter 320ms ease both;
  animation-delay: var(--search-section-delay, 0ms);
}

/* 섹션 타이틀 */
.section-title {
  font-size: 17px;
  font-weight: 700;
  margin: 0 0 var(--title-content-gap);
}

.section-empty {
  width: 100%;
  margin: 0;
  font-size: 13px;
  color: var(--color-text-secondary);
}

.search-section,
.recent-colors-section,
.recommended-colors-section {
  width: 100%;
  margin-bottom: var(--section-gap);
}

.recommended-colors-section {
  margin-bottom: 0;
}

/* 2. 검색창 섹션 */
.search-bar-section {
  flex: 0 0 auto;
  width: 100%;
  padding: 0 0 var(--section-gap);
  margin: 0;
  background-color: #f2f2ee;
}

.search-content-scroll {
  flex: 1 1 auto;
  height: 0;
  min-height: 0;
  overflow-y: auto;
  overscroll-behavior: contain;
  padding-bottom: calc(var(--app-main-bottom) + var(--section-gap));
}

.search-input-box {
  display: flex;
  width: 100%;
  height: 50px;
  align-items: center;
  background-color: #e8e3df;
  padding: 0 18px;
  border-radius: 15px;
}

.icon-search {
  width: 18px;
  margin: 0;
  display: block;
}

.btn-search {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  margin-left: 10px;
}

.search-input-box input {
  border: none;
  background: transparent;
  outline: none;
  font-size: 12px;
  font-weight: 700;
  width: 100%;
  min-width: 0;
  opacity: 0.5;
}

/* 3. 가로 스와이프 공통 */
.search-swiper {
  overflow: visible;
}

.tag-swiper {
  padding: 0;
}

.tag-slide {
  width: auto;
}

.recent-colors-swiper {
  box-sizing: border-box;
  padding: 0 14px;
}

/* 최근 검색어 태그 */
.tag {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  background: #fff;
  padding: 8px 14px;
  border-radius: 20px;
  font-size: 14px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
  cursor: pointer;
}

.btn-delete {
  background: none;
  border: none;
  margin-left: 6px;
  padding: 0;
  cursor: pointer;
}

.btn-delete img {
  width: 10px;
  height: 10px;
  vertical-align: middle;
}

/* 최근 컬러 흰색 배경 박스 */
.recent-colors-wrapper {
  width: 100%;
  margin: 0;
  padding: 15px 0px 10px;
  background: #ffffff;
  border-radius: 15px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
  overflow: hidden;

  position: relative;
}

.recent-colors-empty {
  padding: 0 14px;
}

.color-item {
  width: 65px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  cursor: pointer;
}

.color-slide {
  width: 65px;
}

.color-circle {
  width: 43px;
  height: 43px;
  border-radius: 50%;
  margin-bottom: 6px;
  box-shadow: 0 0 3px rgba(0, 0, 0, 0.18);
}

.color-label {
  font-size: 12px;
  line-height: 0.9;
  width: 100%;
  height: 2.3em;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  white-space: normal;
  word-break: keep-all;
  overflow-wrap: anywhere;
  overflow: hidden;
}

/* 4. 인기 추천 컬러 */
.color-card-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  column-gap: 25px;
  row-gap: 15px;
  padding: 0;
}

.pantone-card {
  width: 100%;
  height: 85px;
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
  cursor: pointer;
}

.card-color-top {
  flex: 1;
}

.card-info-bottom {
  background: #ffffff;
  height: 34px;
  padding: 0 10px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: flex-start;
}

.brand-name {
  font-size: 12px;
  font-weight: 700;
  line-height: 1.2;
  margin-top: 5px;
}

.color-detail-name {
  font-size: 10px;
  font-weight: 600;
  margin-bottom: 6px;
  line-height: 1.2;
}

@keyframes search-section-enter {
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
