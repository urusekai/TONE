<template>
  <main id="palette-log-page" :class="{ 'is-anim': isAnim }">
    <section class="pl">
      <div class="pl-head">
        <h1 class="pl-title">Palette Log</h1>
        <p class="pl-sub">Current Playlist</p>
      </div>

      <p v-if="isLoading" class="pl-state">팔레트 로그를 불러오는 중...</p>
      <p v-else-if="errorMessage" class="pl-state pl-state-error">{{ errorMessage }}</p>
      <p v-else-if="!items.length" class="pl-state">저장된 팔레트 로그가 없습니다.</p>

      <div v-else class="pl-board">
        <ul class="pl-stack" :class="{ 'is-lock': isLock }">
          <li
            v-for="(item, i) in items"
            :key="item.playlist_id"
            class="pl-card"
            :class="{ 'is-in': isAnim, 'is-exit-right': exitId === String(item.playlist_id) }"
            :style="{
              '--card': item.playlist.color_hex,
              '--delay': `${i * 50}ms`,
              zIndex: items.length - i
            }"
          >
            <button
              type="button"
              class="pl-link"
              @click="onCardClick(item)"
              :style="{ color: getTextColor(item.playlist.color_hex) }"
            >
              <span class="pl-arrow" :style="{ filter: getIconFilter(item.playlist.color_hex) }">
                <img :src="arrowIcon" alt=">" />
              </span>

              <h2 class="pl-name">{{ item.playlist.color_name }}</h2>
              <div
                class="pl-line"
                :style="{ background: getLineColor(item.playlist.color_hex) }"
              ></div>

              <div class="pl-meta">
                <span :style="{ filter: getIconFilter(item.playlist.color_hex) }">
                  <img :src="likeIcon" alt="like_full" />
                  {{ formatCount(item.playlist.like_count) }}
                </span>
                <span :style="{ filter: getIconFilter(item.playlist.color_hex) }">
                  <img :src="noteIcon" alt="note" />
                  {{ formatPlays(item.playlist.play_count) }}
                </span>
              </div>
            </button>
          </li>
        </ul>
      </div>
    </section>
  </main>
</template>

<script setup>
import { nextTick, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { apiRequest } from '@/services/httpClient';

import arrowIcon from '@/assets/icons/arrow-right.svg';
import likeIcon from '@/assets/icons/like_full.svg';
import noteIcon from '@/assets/icons/music_note.svg';

const router = useRouter();

const isAnim = ref(false);
const isLoading = ref(false);
const isLock = ref(false);
const errorMessage = ref('');
const exitId = ref(null);
const isTransitioning = ref(false);
const items = ref([]);

const colors = {
  darkText: '#3f5f73',
  lightText: '#F2F2EE'
};

async function loadPaletteLogs() {
  isLoading.value = true;
  errorMessage.value = '';
  isAnim.value = false;
  items.value = [];

  try {
    const result = await apiRequest(
      '/api/palette-logs/list.php',
      {},
      '팔레트 로그를 불러오지 못했습니다.'
    );
    items.value = Array.isArray(result?.paletteLogs) ? result.paletteLogs : [];
  } catch (error) {
    errorMessage.value =
      error instanceof Error ? error.message : '팔레트 로그를 불러오지 못했습니다.';
  } finally {
    isLoading.value = false;
  }
}

async function startEntranceAnimation() {
  await nextTick();
  if (!items.value.length) return;

  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      isAnim.value = true;
    });
  });
}

function onCardClick(item) {
  if (isTransitioning.value) return;

  isTransitioning.value = true;
  isLock.value = true;
  exitId.value = String(item.playlist_id);

  window.setTimeout(() => {
    router.push({
      path: '/playlist',
      query: { id: String(item.playlist_id) },
      state: { fromBottomTab: '/main' }
    });
  }, 350);
}

function parseToRGB(color) {
  if (!color) return null;
  const c = color.trim();

  if (c.startsWith('#')) {
    const hex = c.slice(1);
    if (hex.length !== 6) return null;

    const r = parseInt(hex.slice(0, 2), 16);
    const g = parseInt(hex.slice(2, 4), 16);
    const b = parseInt(hex.slice(4, 6), 16);
    return { r, g, b };
  }

  const matched = c.match(
    /rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)(?:\s*,\s*[\d.]+)?\s*\)/i
  );
  if (matched) {
    return { r: Number(matched[1]), g: Number(matched[2]), b: Number(matched[3]) };
  }

  return null;
}

function getBrightness(color) {
  const rgb = parseToRGB(color);
  if (!rgb) return 0;

  const { r, g, b } = rgb;
  return (r * 299 + g * 587 + b * 114) / 1000;
}

function isBrightColor(color) {
  return getBrightness(color) > 170;
}

function getTextColor(color) {
  return isBrightColor(color) ? colors.darkText : colors.lightText;
}

function getLineColor(color) {
  return isBrightColor(color) ? 'rgba(63, 95, 115, 0.28)' : 'rgba(255, 255, 255, 0.32)';
}

function getIconFilter(color) {
  return isBrightColor(color)
    ? 'brightness(0) saturate(100%) invert(31%) sepia(18%) saturate(653%) hue-rotate(157deg) brightness(91%) contrast(88%)'
    : 'brightness(0) invert(1)';
}

function formatCount(value) {
  return Number(value || 0).toLocaleString('en-US');
}

function formatPlays(value) {
  return `${Number(value || 0).toLocaleString('en-US')} Plays`;
}

onMounted(async () => {
  await loadPaletteLogs();
});

watch(
  items,
  async (nextItems) => {
    if (!nextItems.length) return;
    await startEntranceAnimation();
  },
  { deep: true }
);
</script>
<style scoped>
/* =========================
   Palette Log (reset)
   ========================= */
* {
  box-sizing: border-box;
}

/* main 영역: 부모 flex 센터링 영향 차단 */
#palette-log-page {
  display: block;
  width: 100%;
  margin: 0;
  align-self: stretch;
  position: relative;
  z-index: 2;
  will-change: transform;
  transition: transform 0.45s cubic-bezier(0.2, 0.8, 0.2, 1);
}

/* 헤더 텍스트 */
.pl-head {
  margin: 8px 0 70px;
}

.pl-title {
  font-size: 25px;
  font-weight: 800;
  letter-spacing: -0.02em;
  line-height: 1.15;
}

.pl-sub {
  margin-top: 4px;
  font-size: 14px;
  font-weight: 800;
  opacity: 0.75;
}

.pl-state {
  padding: 12px 0;
  font-size: 14px;
  color: #3f5f73;
  text-align: center;
}

.pl-state-error {
  color: #b42318;
}

/* =========================
   Board
   ========================= */
.pl-board {
  width: 100%;
  height: auto;
  margin: 0 auto;
  position: relative;
  padding-top: 22px;
  overflow: visible;
  background: #e1d8d6;
  box-shadow:
    0px 2px 4px 2px rgba(0, 0, 0, 0.2),
    0px 4px 4px 0px rgba(0, 0, 0, 0.25);
}

/* =========================
   Stack + Cards
   ========================= */
.pl-stack {
  position: relative;
  z-index: 1;
  list-style: none;
  padding-top: 0;
  padding-bottom: 70px;
  margin: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.pl-card {
  width: 312px;
  height: 130px;
  background: var(--card, #bfc9d6);
  overflow: hidden;
  margin-top: -28px;
  box-shadow:
    0 16px 26px rgba(0, 0, 0, 0.18),
    0 1px 0 rgba(255, 255, 255, 0.25) inset;
  opacity: 0;
  transform: translateY(10px);
  will-change: transform, opacity;
  transition:
    opacity 0.36s ease,
    transform 0.36s ease;
  transition-delay: var(--delay, 0ms);
}

.pl-card:first-child {
  margin-top: -70px;
}

#palette-log-page.is-anim .pl-card.is-in {
  opacity: 1;
  transform: translateY(0);
}

/* 링크 내부 */
.pl-link {
  display: block;
  width: 100%;
  height: 100%;
  position: relative;
  padding: 14px 14px 12px;
  text-decoration: none;
  color: #fff;

  /* button reset */
  border: 0;
  background: transparent;
  text-align: left;
  cursor: pointer;
}

/* 화살표 */
.pl-arrow {
  position: absolute;
  right: 12px;
  top: 45px;
  opacity: 0.9;
}

.pl-arrow img {
  display: block;
  width: 6px;
  height: 10px;
  filter: brightness(0) invert(1);
}

.pl-card:first-child .pl-arrow {
  top: 40px;
}

/* 제목 */
.pl-name {
  margin: 10px 0 8px;
  padding-top: 10px;
  padding-left: 5px;
  padding-bottom: 5px;
  font-size: 30px;
  font-weight: 700;
  letter-spacing: -0.02em;
  text-align: left;
}

.pl-card:first-child .pl-name {
  position: relative;
  top: -10px;
}

/* 구분선 */
.pl-line {
  height: 1px;
  background: rgba(255, 255, 255, 0.32);
  margin: 8px 2px 10px;
}

/* 메타 */
.pl-meta {
  display: flex;
  padding-top: 5px;
  gap: 14px;
  align-items: center;
  font-size: 13px;
  font-weight: 700;
  color: #fff;
  opacity: 1;
}

.pl-meta span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.pl-meta img {
  width: 12px;
  height: 12px;
  opacity: 1;
  filter: brightness(0) invert(1);
}

/* =========================
   B) 오른쪽으로 빠지는 전환
========================= */
#palette-log-page.is-anim .pl-card.is-exit-right {
  transform: translateX(140%);
  opacity: 0;
  transition:
    transform 1.5s cubic-bezier(0.2, 0.8, 0.2, 1),
    opacity 0.25s ease;
}

/* 클릭 순간 다른 카드 잠금 */
.pl-stack.is-lock .pl-card:not(.is-exit-right) {
  pointer-events: none;
}
</style>
