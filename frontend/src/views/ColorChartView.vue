<template>
  <main class="cc-page" aria-label="Color Charts">
    <h1 class="cc-title">Color Charts</h1>

    <ul class="cc-list">
      <li
        v-for="item in chartItems"
        :key="item.playlistId"
        class="cc-item"
        :class="{ 'is-active': activePid === item.playlistId }"
        :style="{ '--color': item.colorHex }"
      >
        <!-- ✅ 카드 전체 클릭: /playlist?pid=... -->
        <!-- (기존 html의 a.cc-link 역할) -->
        <a
          class="cc-link"
          href="#"
          :aria-label="`플레이리스트 ${item.rank} 열기`"
          @click.prevent="onCardClick(item.playlistId)"
        >
          <div class="cc-left">
            <p class="cc-kicker">Playlist</p>
            <div class="cc-row">
              <p class="cc-no">{{ pad2(item.rank) }}</p>
              <div class="cc-meta">
                <p class="cc-code">{{ item.pantoneCode }}</p>
                <p class="cc-name">{{ item.colorName }}</p>
              </div>
            </div>
          </div>

          <!-- ✅ LP: active 카드만 회전 (CSS 그대로) -->
          <div class="cc-vinyl" aria-hidden="true">
            <img :src="vinylImg" alt="" />
          </div>

          <div class="cc-right">
            <!-- 좋아요 -->
            <div class="cc-like">
              <span
                class="cc-like-count"
                :style="{ color: isBright(item.colorHex) ? colors.darkText : colors.lightText }"
              >
                {{ formatLikes(item.likes) }}
              </span>

              <button
                type="button"
                class="cc-like-btn"
                aria-label="좋아요"
                @click.stop.prevent="toggleLike(item)"
                title="좋아요"
              >
                <img
                  :src="item.liked ? likeFullIcon : likeIcon"
                  alt=""
                  :style="{ filter: isBright(item.colorHex) ? 'none' : 'brightness(0) invert(1)' }"
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
                :src="paletteLog.has(item.playlistId) ? addCompleteIcon : addIcon"
                alt=""
                :style="{ filter: isBright(item.colorHex) ? 'none' : 'brightness(0) invert(1)' }"
              />
            </button>
          </div>
        </a>
      </li>
    </ul>
  </main>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { usePaletteLogStore } from '@/stores/paletteLog';

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

/** active 카드(= LP 회전 대상) */
const activePid = ref('');

/**
 * ✅ 더미 데이터(확장 가능)
 * 요구 모델: week, rank, prevRank, likes, playlistId(pid), colorName, pantoneCode, colorHex, hashtags, totalTracks ...
 * - 현재 UI는 pantoneCode / colorName / rank / likes / colorHex 사용
 */
const chartItems = ref([
  {
    week: '2026-W10',
    rank: 1,
    prevRank: 2,
    likes: 12300,
    playlistId: 'cc-01',
    pantoneCode: '17-3938',
    colorName: 'Very Peri',
    colorHex: '#6667ab',
    hashtags: ['emotional', 'soft'],
    totalTracks: 24,
    liked: false
  },
  {
    week: '2026-W10',
    rank: 2,
    prevRank: 1,
    likes: 12200,
    playlistId: 'cc-02',
    pantoneCode: '16-1546',
    colorName: 'Living Coral',
    colorHex: '#ff6f61',
    hashtags: ['bright', 'warm'],
    totalTracks: 28,
    liked: false
  },
  {
    week: '2026-W10',
    rank: 3,
    prevRank: 4,
    likes: 11700,
    playlistId: 'cc-03',
    pantoneCode: '18-1750',
    colorName: 'Viva Magenta',
    colorHex: '#bb2649',
    hashtags: ['bold', 'statement'],
    totalTracks: 26,
    liked: false
  },
  {
    week: '2026-W10',
    rank: 4,
    prevRank: 6,
    likes: 11056,
    playlistId: 'cc-04',
    pantoneCode: '19-4052',
    colorName: 'Classic Blue',
    colorHex: '#34568b',
    hashtags: ['focus', 'clean'],
    totalTracks: 22,
    liked: false
  },
  {
    week: '2026-W10',
    rank: 5,
    prevRank: 5,
    likes: 10500,
    playlistId: 'cc-05',
    pantoneCode: '13-0647',
    colorName: 'Illuminating',
    colorHex: '#f5df4d',
    hashtags: ['daylight', 'spark'],
    totalTracks: 20,
    liked: false
  },
  {
    week: '2026-W10',
    rank: 6,
    prevRank: 7,
    likes: 10120,
    playlistId: 'cc-06',
    pantoneCode: '15-5519',
    colorName: 'Turquoise',
    colorHex: '#7fcdcd',
    hashtags: ['chill', 'fresh'],
    totalTracks: 21,
    liked: false
  },
  {
    week: '2026-W10',
    rank: 7,
    prevRank: 8,
    likes: 9880,
    playlistId: 'cc-07',
    pantoneCode: '13-1023',
    colorName: 'Rose Quartz',
    colorHex: '#d19c97',
    hashtags: ['soft', 'warm'],
    totalTracks: 23,
    liked: false
  },
  {
    week: '2026-W10',
    rank: 8,
    prevRank: 10,
    likes: 9540,
    playlistId: 'cc-08',
    pantoneCode: '17-1463',
    colorName: 'Tiffany Blue',
    colorHex: '#44b5aa',
    hashtags: ['clean', 'airy'],
    totalTracks: 25,
    liked: false
  },
  {
    week: '2026-W10',
    rank: 9,
    prevRank: 9,
    likes: 9120,
    playlistId: 'cc-09',
    pantoneCode: '19-4050',
    colorName: 'Blue Iris',
    colorHex: '#00539c',
    hashtags: ['nightdrive', 'deep'],
    totalTracks: 24,
    liked: false
  },
  {
    week: '2026-W10',
    rank: 10,
    prevRank: 3,
    likes: 8760,
    playlistId: 'cc-10',
    pantoneCode: '15-0343',
    colorName: 'Greenery',
    colorHex: '#88b04b',
    hashtags: ['nature', 'steady'],
    totalTracks: 19,
    liked: false
  }
]);

/** 기존 JS 기준 색(밝기 기준 넘으면 어두운 텍스트, 아니면 밝은 텍스트) */
const colors = {
  darkText: '#6B6E6E', // Hematite
  lightText: '#F2F2EE' // Cloud Dancer
};

/** 카드 클릭: active 토글 + /playlist?pid */
function onCardClick(pid) {
  // 1) 먼저 active 켜서 LP 회전 트리거
  activePid.value = pid;

  // 2) 짧게 보여주고 이동
  window.setTimeout(() => {
    router.push({ path: '/playlist', query: { pid } });
  }, 350); // 여기서 넘어가는 시간 조절
}

/** add 버튼: /calendar */
function goCalendar() {
  router.push({ path: '/calendar' });
}

/** 좋아요(임시 동작: UI/데이터 확장용) */
function toggleLike(item) {
  item.liked = !item.liked;
  item.likes += item.liked ? 1 : -1;
}

/** 2자리 랭크 표기 */
function pad2(n) {
  return String(n).padStart(2, '0');
}

/** 12,300 포맷 */
function formatLikes(n) {
  return Number(n).toLocaleString('en-US');
}

/**
 * ✅ 기존 color-chart.js의 brightness 로직을 Vue로 이식
 * 기준값 170 그대로 유지
 */
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
    playlistId: item.playlistId,
    // Palette Log에서 쓰기 좋은 최소 메타
    colorName: item.colorName,
    pantoneCode: item.pantoneCode,
    colorHex: item.colorHex,
    likes: item.likes,
    week: item.week,
    rank: item.rank,
    totalTracks: item.totalTracks,
    hashtags: item.hashtags
  });
}
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
