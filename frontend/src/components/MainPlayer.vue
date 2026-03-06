<script setup>
import { ref, computed, watch } from 'vue';
import { usePlayerStore } from '@/stores/player';
import { useAuthStore } from '@/stores/auth';

import arrowDown from '@/assets/icons/arrow-down.svg';
import arrowDownLight from '@/assets/icons/arrow-down_light.svg';

const player = usePlayerStore();
const authStore = useAuthStore();

/* ✅ MV 모드 */
const isMV = ref(false);

/* ✅ currentTrack 방어 + 필요한 필드 기본값 */
const track = computed(() => {
  const t = player.currentTrack ?? {};
  return {
    title: t.title ?? 'Title',
    artist: t.artist ?? 'Artist',
    cover: t.cover ?? '',
    colorName: t.colorName ?? 'Very Peri',
    pantoneCode: t.pantoneCode ?? '17-3938',
    toneBg: t.toneBg ?? '#6868AB',
    mvUrl: t.mvUrl ?? '',
    ...t
  };
});

function handleCloseMain() {
  isMV.value = false;
  player.closeMain();
}

function toggleMV() {
  isMV.value = !isMV.value;
}

/* ✅ 열릴 때마다 MV 초기화(권장) */
watch(
  () => player.isMain,
  (open) => {
    if (open) isMV.value = false;
  }
);

/* ✅ HEX 색상 → 상대 명도 계산 */
function hexToRgb(hex) {
  if (!hex) return null;
  let h = String(hex).replace('#', '').trim();
  if (h.length === 3)
    h = h
      .split('')
      .map((c) => c + c)
      .join('');
  if (h.length !== 6) return null;
  const n = parseInt(h, 16);
  return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 };
}
function srgbToLinear(v) {
  v /= 255;
  return v <= 0.04045 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
}
function luminanceFromHex(hex) {
  const rgb = hexToRgb(hex);
  if (!rgb) return 1;
  const R = srgbToLinear(rgb.r);
  const G = srgbToLinear(rgb.g);
  const B = srgbToLinear(rgb.b);
  return 0.2126 * R + 0.7152 * G + 0.0722 * B;
}

/* ✅ 배경색 기반으로 텍스트/아이콘 색 결정 */
const isDarkTone = computed(() => {
  const lum = luminanceFromHex(track.value.toneBg);
  return lum < 0.55; // 기준값 필요하면 조절
});

/* ✅ 텍스트/도트 컬러 */
const toneText = computed(() => (isDarkTone.value ? '#F2F2EE' : '#3F5F73'));
const toneTextSub = computed(() =>
  isDarkTone.value ? 'rgba(242,242,238,0.78)' : 'rgba(63,95,115,0.75)'
);
const toneDotOff = computed(() =>
  isDarkTone.value ? 'rgba(242,242,238,0.25)' : 'rgba(63,95,115,0.25)'
);
const toneDotOn = computed(() =>
  isDarkTone.value ? 'rgba(242,242,238,0.85)' : 'rgba(63,95,115,0.85)'
);

/* ✅ 아이콘 src 스위칭 */
const iconArrow = computed(() => (isDarkTone.value ? arrowDownLight : arrowDown));

/* ✅ CSS 변수로 내려주기 */
const toneVars = computed(() => ({
  '--tone-text': toneText.value,
  '--tone-sub': toneTextSub.value,
  '--tone-dot-off': toneDotOff.value,
  '--tone-dot-on': toneDotOn.value
}));
</script>

<template>
  <section class="main-player" :class="{ 'is-active': player.isMain }">
    <div class="main-player__media" :class="{ 'is-mv': isMV }" :style="toneVars">
      <header
        class="main-player__header"
        :class="{ 'is-mv': isMV }"
        :style="{ background: track.toneBg }"
      >
        <button type="button" class="main-player__back-btn" @click="handleCloseMain">
          <img :src="iconArrow" alt="메인플레이어 닫기" />
        </button>

        <div class="main-player__header-center" :class="{ 'is-active': isMV, 'is-empty': !isMV }">
          <p class="main-player__header-label">PLAYLIST</p>
          <p class="main-player__header-name">{{ track.colorName }}</p>
        </div>

        <button
          type="button"
          class="main-player__slide_dot"
          @click="toggleMV"
          aria-label="화면 전환"
        >
          <span class="main-player__dot-ui" :class="{ 'is-mv': isMV }" aria-hidden="true">
            <span class="main-player__dot-slot"></span>
            <span class="main-player__dot-slot"></span>
            <span class="main-player__dot-active"></span>
          </span>
        </button>
      </header>

      <div v-if="isMV" class="main-player__mv">
        <video
          class="main-player__mv-video"
          :src="track.mvUrl"
          autoplay
          muted
          playsinline
          controls
        ></video>
      </div>

      <div v-else class="main-player__tone" :style="{ background: track.toneBg }">
        <div class="main-player__tone-meta" :class="{ 'is-hidden': isMV }">
          <p class="main-player__color-name">{{ track.colorName }}</p>
          <p class="main-player__color-code">{{ track.pantoneCode }}</p>
        </div>

        <button
          type="button"
          class="main-player__cover-circle-btn"
          @click="toggleMV"
          aria-label="뮤직비디오 보기"
        >
          <img
            v-if="track.cover"
            :src="track.cover"
            alt="앨범커버"
            class="main-player__cover-circle"
          />
        </button>
      </div>
    </div>

    <div class="main-player__info">
      <p class="main-player__title">{{ track.title }}</p>
      <p class="main-player__artist">{{ track.artist }}</p>
    </div>

    <div class="main-player__progress">
      <div class="main-player__progress-cover" style="--progress: 65%"></div>
    </div>

    <div class="main-player__time">
      <span>00:00</span>
      <span>00:00</span>
    </div>

    <div class="main-player__actions-top">
      <button type="button" class="main-player__prev-btn">
        <img src="@/assets/icons/prev-song.svg" alt="이전곡" />
      </button>
      <button type="button" class="main-player__play-toggle-btn is-playing">
        <img src="@/assets/icons/pause-circle.svg" alt="일시정지" />
        <img src="@/assets/icons/play-circle.svg" alt="재생" />
      </button>
      <button type="button" class="main-player__next-btn">
        <img src="@/assets/icons/next-song.svg" alt="다음곡" />
      </button>
    </div>

    <div class="main-player__actions-bottom">
      <button type="button" class="main-player__like-btn">
        <img src="@/assets/icons/like.svg" alt="좋아요" />
        <img src="@/assets/icons/like_full.svg" alt="좋아요" />
      </button>
      <button type="button" class="main-player__shuffle-btn">
        <img src="@/assets/icons/shuffle.svg" alt="셔플" />
      </button>
      <button type="button" class="main-player__repeat-btn">
        <img src="@/assets/icons/repeat.svg" alt="반복" />
      </button>
      <button type="button" class="main-player__add-btn">
        <img src="@/assets/icons/add.svg" alt="추가" />
      </button>
    </div>
  </section>
</template>

<style scoped>
/* ==================================================
   메인 플레이어
================================================== */

.main-player {
  position: fixed;
  top: 0;
  bottom: 0;
  left: 50%;
  transform: translate(-50%, 24px);
  width: 100%;
  max-width: 402px;
  z-index: 2000;

  display: flex;
  flex-direction: column;

  background: var(--color-bg-app);
  padding: 0 var(--layout-x) 25px;
  overflow-y: auto;

  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transition:
    opacity 0.3s ease,
    transform 0.3s ease,
    visibility 0s linear 0.3s;
}

.main-player.is-active {
  transform: translate(-50%, 0);
  opacity: 1;
  visibility: visible;
  pointer-events: auto;
  transition:
    opacity 0.3s ease,
    transform 0.3s ease,
    visibility 0s linear 0s;
}

/* ==================================================
   상단 media
================================================== */
.main-player__media {
  margin-left: calc(var(--layout-x) * -1);
  margin-right: calc(var(--layout-x) * -1);
  overflow: hidden;
  box-shadow: 0px 4px 4px 0px rgba(0, 0, 0, 0.25);

  /* AUDIO 기본 높이 */
  height: 500px;

  display: flex;
  flex-direction: column;
}

/* MV일 때는 전체 높이를 내용만큼만 */
.main-player__media.is-mv {
  height: auto;
}

/* ==================================================
   헤더
================================================== */
.main-player__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 25px;
  flex-shrink: 0;
}

.main-player__back-btn {
  width: 34px;
  height: 34px;
  flex: none;
  display: flex;
  align-items: center;
  justify-content: flex-start;
}

.main-player__header-center {
  flex: 1;
  text-align: center;
  line-height: 1.1;

  opacity: 0;
  transform: translateY(-10px);
  pointer-events: none;

  transition:
    opacity 0.28s ease,
    transform 0.42s cubic-bezier(0.22, 1, 0.36, 1);
}

.main-player__header-center.is-active {
  opacity: 1;
  transform: translateY(0);
  pointer-events: auto;
}

.main-player__header-center.is-empty {
  opacity: 0;
  pointer-events: none;
}

.main-player__header-label {
  margin: 0;
  font-size: 12px;
  opacity: 0.5;
  font-weight: 600;
}

.main-player__header-name {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
}

.main-player__slide_dot {
  width: 34px;
  height: 34px;
  flex: none;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* ==================================================
   코드로 만든 닷 (morph + slide)
================================================== */
.main-player__dot-ui {
  position: relative;
  width: 34px;
  height: 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.main-player__dot-slot {
  width: 14px;
  height: 14px;
  border-radius: 999px;
  background: var(--tone-dot-off);
  flex: 0 0 14px;
}

/* 활성 닷 */
.main-player__dot-active {
  position: absolute;
  top: 50%;
  left: 0;
  width: 14px;
  height: 14px;
  border-radius: 999px;
  background: var(--tone-dot-on);
  transform: translateY(-50%);
}

/* 오디오 -> 뮤비 */
.main-player__dot-ui:not(.is-mv) .main-player__dot-active {
  animation: dotToLeft 0.42s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.main-player__dot-ui.is-mv .main-player__dot-active {
  left: 20px;
  animation: dotToRight 0.42s cubic-bezier(0.22, 1, 0.36, 1) both;
}

/* 왼쪽에서 오른쪽으로 */
@keyframes dotToRight {
  0% {
    left: 0;
    width: 14px;
  }
  65% {
    left: 0;
    width: 34px;
  }
  100% {
    left: 20px;
    width: 14px;
  }
}

@keyframes dotToLeft {
  0% {
    left: 20px;
    width: 14px;
  }
  65% {
    left: 0;
    width: 34px;
  }
  100% {
    left: 0;
    width: 14px;
  }
}

/* ==================================================
   AUDIO 화면
================================================== */
.main-player__tone {
  position: relative;
  width: 100%;
  flex: 1;
  margin-top: -1px;
}

/* 왼쪽 큰 타이포 */
.main-player__color-name {
  position: absolute;
  left: 25px;
  top: 10px;
  margin: 0;
  font-size: 36px;
  font-weight: 700;
  line-height: 1.05;
  color: #3f5f73;
  text-shadow: 0px 0px 4px rgba(0, 0, 0, 0.25);
}

.main-player__color-code {
  position: absolute;
  left: 25px;
  top: 50px;
  margin: 0;
  font-size: 20px;
  font-weight: 500;
  color: #3f5f73;
  text-shadow: 0px 0px 4px rgba(0, 0, 0, 0.25);
}

.main-player__tone-meta {
  transition:
    opacity 0.24s ease,
    transform 0.36s cubic-bezier(0.22, 1, 0.36, 1);
  opacity: 1;
  transform: translateY(0);
}

.main-player__tone-meta.is-hidden {
  opacity: 0;
  transform: translateY(-8px);
  pointer-events: none;
}

/* 동그란 커버 */
.main-player__cover-circle-btn {
  position: absolute;
  right: 25px;
  bottom: 20px;
  width: 100px;
  height: 100px;
  border-radius: 999px;
  border: 5px solid #fff;
  padding: 0;
  background: transparent;
  box-shadow: 0px 0px 4px 0px rgba(0, 0, 0, 0.25);
}

.main-player__cover-circle {
  width: 100%;
  height: 100%;
  border-radius: 999px;
  object-fit: cover;
  display: block;
}

/* ==================================================
   MV 화면
================================================== */
.main-player__mv {
  width: 100%;
  height: 430px;
  background: #000;
  flex-shrink: 0;
}

.main-player__mv-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

/* ==================================================
   이하 기존 UI
================================================== */
.main-player__info {
  text-align: center;
  margin-top: 26px;
}

.main-player__title {
  font-size: 36px;
  font-weight: 700;
  margin: 0;
  color: #3f5f73;
}

.main-player__artist {
  margin: 6px 0 0;
  font-size: 20px;
  font-weight: 500;
}

.main-player__progress {
  position: relative;
  width: 100%;
  height: 5px;
  margin-top: auto;
  border-radius: 999px;
  overflow: hidden;
  background: linear-gradient(90deg, #a8d4e6 0%, #c3b7d6 49%, #f5c9c6 100%);
}

.main-player__progress-cover {
  position: absolute;
  top: 0;
  right: 0;
  width: calc(100% - var(--progress, 0%));
  height: 100%;
  background: #e5e6e5;
  transition: width 0.2s linear;
}

.main-player__time {
  position: relative;
  width: 100%;
  height: 0;
  margin-top: 0;
  color: var(--color-text-primary);
  font-size: 14px;
  font-weight: 500;
}

.main-player__time span {
  position: absolute;
  top: 8px;
  line-height: 1;
}

.main-player__time span:first-child {
  left: 0;
}

.main-player__time span:last-child {
  right: 0;
}

.main-player__actions-top {
  margin-top: auto;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 52px;
}

.main-player__prev-btn img,
.main-player__next-btn img {
  width: 20px;
  height: 20px;
}

.main-player__play-toggle-btn {
  display: flex;
  align-items: center;
  justify-content: center;
}

.main-player__play-toggle-btn img {
  display: none;
  width: 50px;
  height: 50px;
}

.main-player__play-toggle-btn img:last-child {
  display: block;
}

.main-player__play-toggle-btn.is-playing img:first-child {
  display: block;
}

.main-player__play-toggle-btn.is-playing img:last-child {
  display: none;
}

.main-player__actions-bottom {
  padding: 0;
  margin-top: auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}

.main-player__actions-bottom button img {
  width: 25px;
  height: 25px;
}

.main-player__like-btn img:last-child {
  display: none;
}

.main-player__like-btn.is-liked img:first-child {
  display: none;
}

.main-player__like-btn.is-liked img:last-child {
  display: block;
}

/* 상단 텍스트 컬러는 tone 기반 */
.main-player__header-label,
.main-player__header-name,
.main-player__color-name,
.main-player__color-code {
  color: var(--tone-text) !important;
}

.main-player__header-label {
  color: var(--tone-sub) !important;
}
</style>
