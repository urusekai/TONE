<template>
  <main class="cc-page" aria-label="Color Charts">
    <h1 class="cc-title">Color Charts</h1>

    <p v-if="isLoading" class="cc-state">컬러차트를 불러오는 중...</p>
    <p v-else-if="errorMessage" class="cc-state cc-state-error">{{ errorMessage }}</p>

    <ul v-else class="cc-list">
      <li
        v-for="(item, index) in chartItems"
        :key="item.id"
        class="cc-item"
        :class="{ 'is-active': activePid === String(item.id) }"
        :style="{ '--color': item.color_hex }"
      >
        <a
          class="cc-link"
          href="#"
          :aria-label="`플레이리스트 ${pad2(index + 1)} 열기`"
          @click.prevent="onCardClick(item.id)"
        >
          <div class="cc-left">
            <p class="cc-kicker">Playlist</p>
            <div class="cc-row">
              <p class="cc-no">{{ pad2(index + 1) }}</p>
              <div class="cc-meta">
                <p class="cc-code">{{ item.pantone_code }}</p>
                <p class="cc-name">{{ item.color_name }}</p>
              </div>
            </div>
          </div>

          <div class="cc-vinyl" aria-hidden="true">
            <img :src="vinylImg" alt="" />
          </div>

          <div class="cc-right">
            <div class="cc-like">
              <span
                class="cc-like-count"
                :style="{ color: isBright(item.color_hex) ? colors.darkText : colors.lightText }"
              >
                {{ formatLikes(item.like_count) }}
              </span>

              <button
                type="button"
                class="cc-like-btn"
                aria-label="좋아요"
                :disabled="isLikePending(item.id)"
                @click.stop.prevent="handleToggleLike(item)"
                title="좋아요"
              >
                <img
                  :src="item.liked ? likeFullIcon : likeIcon"
                  alt=""
                  :style="{ filter: isBright(item.color_hex) ? 'none' : 'brightness(0) invert(1)' }"
                />
              </button>
            </div>

            <button
                type="button"
                class="cc-add"
                aria-label="팔레트 로그에 저장"
                @click.capture.stop.prevent="togglePalette(item)"
                title="팔레트 로그에 저장"
              >
                <img
                :src="paletteLog.has(String(item.id)) ? addCompleteIcon : addIcon"
                alt=""
                :style="{ filter: isBright(item.color_hex) ? 'none' : 'brightness(0) invert(1)' }"
                />
              </button>
          </div>
        </a>
      </li>
    </ul>
  </main>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { usePaletteLogStore } from '@/stores/paletteLog';
import { apiRequest } from '@/services/httpClient';

/**
 * ✅ 에셋 경로는 전부 @/assets 로 통일
 * - 기존: ./assets/images/Ellipse.png, ./assets/icons/like.svg, ./assets/icons/add.svg
 */
import vinylImg from '@/assets/images/Ellipse.png';
import likeIcon from '@/assets/icons/like.svg';
import addIcon from '@/assets/icons/add.svg';
import addCompleteIcon from '@/assets/icons/addComplete.svg';
import likeFullIcon from '@/assets/icons/like_full.svg';

const router = useRouter();
const paletteLog = usePaletteLogStore();

const activePid = ref('');
const isLoading = ref(false);
const errorMessage = ref('');
const pendingLikeMap = ref({});
const chartItems = ref([]);

const colors = {
  darkText: '#3f5f73',
  lightText: '#F2F2EE'
};

function onCardClick(id) {
  activePid.value = String(id);

  window.setTimeout(() => {
    router.push({ path: '/playlist', query: { id } });
  }, 350);
}

function isLikePending(id) {
  return Boolean(pendingLikeMap.value[String(id)]);
}

function setLikePending(id, value) {
  pendingLikeMap.value = {
    ...pendingLikeMap.value,
    [String(id)]: value
  };
}

async function handleToggleLike(item) {
  const itemId = String(item?.id || '');
  if (!itemId || isLikePending(itemId)) return;

  setLikePending(itemId, true);

  try {
    const result = await apiRequest(
      '/api/playlist/like.php',
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          playlist_id: Number(item.id)
        })
      },
      '좋아요 처리에 실패했습니다.'
    );

    item.liked = Boolean(result?.liked);
    item.like_count = Number(result?.like_count || 0);
  } catch (error) {
    const message = error instanceof Error ? error.message : '좋아요 처리에 실패했습니다.';
    window.alert(message);
  } finally {
    setLikePending(itemId, false);
  }
}

async function loadChartItems() {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const result = await apiRequest(
      '/api/playlist/chart.php',
      {},
      '컬러차트를 불러오지 못했습니다.'
    );
    chartItems.value = Array.isArray(result?.playlists) ? result.playlists : [];
  } catch (error) {
    chartItems.value = [];
    errorMessage.value =
      error instanceof Error ? error.message : '컬러차트를 불러오지 못했습니다.';
  } finally {
    isLoading.value = false;
  }
}

function pad2(n) {
  return String(n).padStart(2, '0');
}

function formatLikes(n) {
  return Number(n).toLocaleString('en-US');
}

function isBright(hex) {
  const c = (hex || '').replace('#', '').trim();
  if (c.length !== 6) return false;

  const r = parseInt(c.slice(0, 2), 16);
  const g = parseInt(c.slice(2, 4), 16);
  const b = parseInt(c.slice(4, 6), 16);

  const brightness = (r * 299 + g * 587 + b * 114) / 1000;
  return brightness > 170;
}

function togglePalette(item) {
  paletteLog.toggle({
    playlistId: String(item.id),
    colorName: item.color_name,
    pantoneCode: item.pantone_code,
    colorHex: item.color_hex,
    likes: item.like_count,
    mood: item.mood,
    totalTracks: item.totalTracks,
    hashtags: [item.mood]
  });
}

onMounted(async () => {
  await loadChartItems();
});
</script>

<style scoped>
/* ✅ 기존 color-chart.css를 그대로 이식 (필요한 부분만) */
/* =========================
   Color Chart Page
   ========================= */
.app main.cc-page {
  display: block;
  width: 100%;
  margin: 0;
  align-self: stretch;
  justify-content: unset;
  align-items: unset;
}

.cc-title {
  font-size: 26px;
  font-weight: 800;
  letter-spacing: -0.02em;
  margin: 4px 0 10px;
}

.cc-list {
  display: grid;
  gap: 10px;
}

.cc-state {
  padding: 12px 0;
  font-size: 14px;
  color: #3f5f73;
  text-align: center;
}

.cc-state-error {
  color: #b42318;
}

/* 카드(리스트 아이템) */
.cc-item {
  width: 100%;
  max-width: 363px;
  height: 80px;
  background-color: #f6f6f3;
  border-radius: 10px;
  border: 1px solid rgba(183, 174, 166, 0.5);
  box-shadow: 0px 0px 4px 0px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.cc-link {
  position: relative;
  display: flex;
  min-height: 80px;
  text-decoration: none;
  color: inherit;
}

/* 왼쪽 텍스트 영역 */
.cc-left {
  flex: 0 0 50%;
  padding: 3px 0 5px 10px;
  min-width: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: flex-start;
}

.cc-kicker {
  font-size: 12px;
  font-weight: 800;
  color: rgba(25, 30, 35, 0.4);
}

.cc-row {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.cc-no {
  font-size: 16px;
  font-weight: 900;
  line-height: 1;
  min-width: 0;
  margin-bottom: 6px;
}

.cc-meta {
  display: flex;
  flex-direction: column;
}

.cc-code {
  font-size: 10px;
  font-weight: 800;
  line-height: 1.1;
  margin-bottom: -1px;
}

.cc-name {
  font-size: 16px;
  font-weight: 900;
  line-height: 1.05;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* 바이닐(중간 겹침) */
.cc-vinyl {
  position: absolute;
  left: 50%;
  top: calc(50% - 1px);
  transform: translate(-50%, -50%);
  width: 82px;
  height: 82px;
  opacity: 0.92;
  pointer-events: none;
  filter: drop-shadow(0 8px 12px rgba(11, 14, 25, 0.12));
}

.cc-vinyl img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

/* 오른쪽 컬러 영역 */
.cc-right {
  flex: 0 0 50%;
  width: auto;
  position: relative;
  background: var(--color);
  overflow: visible;
}

.cc-right::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.15), rgba(0, 0, 0, 0.06));
  pointer-events: none;
}

/* 좋아요(상단 우측) */
.cc-like {
  position: absolute;
  right: 10px;
  top: 8px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.cc-like-count {
  font-size: 10px;
  font-weight: 800;
}

.cc-like-btn {
  width: 18px;
  height: 18px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  background: transparent;
  padding: 0;
}

.cc-like-btn:disabled {
  cursor: default;
  opacity: 0.7;
}

.cc-like-btn img {
  width: 16px;
  height: 14px;
  display: block;
}

/* add 버튼(하단 우측) */
.cc-add {
  position: absolute;
  right: 10px;
  bottom: 8px;
  width: 22px;
  height: 22px;
  border: 0;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  background: transparent;
}

.cc-add img {
  width: 12px;
  height: 12px;
}

/* 터치/호버 */
.cc-item:active {
  transform: translateY(1px);
}

.cc-link:focus-visible {
  outline: 2px solid rgba(110, 131, 247, 0.55);
  outline-offset: 2px;
  border-radius: 12px;
}

@keyframes lpSpin {
  from {
    transform: translate(-50%, -50%) rotate(0deg);
  }
  to {
    transform: translate(-50%, -50%) rotate(360deg);
  }
}

/* ✅ active 카드만 회전 */
.cc-item.is-active .cc-vinyl {
  animation: lpSpin 1.05s linear infinite;
}
</style>
