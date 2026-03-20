<template>
  <!-- 페이지 내용 -->
  <main ref="rootEl" id="main-page" class="home" :class="{ 'is-enter-ready': isEnterReady }">
    <!-- 1) Daily tone -->
    <section class="panel daily" style="--panel-delay: 0ms">
      <div class="daily-pill">Daily tone</div>

      <p v-if="isDailyLoading" class="daily-state">오늘의 톤을 불러오는 중...</p>
      <p v-else-if="dailyErrorMessage" class="daily-state daily-state-error">
        {{ dailyErrorMessage }}
      </p>

      <div v-else-if="dailyPlaylist" class="daily-inner">
        <div class="daily-text">
          <h2 :style="dailyToneAccentStyle">{{ dailyPlaylist.color_name }}</h2>
          <p class="daily-description" aria-live="polite">
            <span>{{ typedDailyIntro.prefix }}</span>
            <b v-if="typedDailyIntro.name" :style="dailyToneAccentStyle">{{
              typedDailyIntro.name
            }}</b>
            <span>{{ typedDailyIntro.suffix }}</span>
            <br v-if="typedDailyIntro.secondLine" />
            <span>{{ typedDailyIntro.secondLine }}</span>
          </p>

          <PlaylistActionControls
            class="daily-actions"
            surface="white"
            :play-color="dailyPlaylist.color_hex"
            :saved="paletteLog.has(dailyPlaylist.id)"
            :play-disabled="!dailyPlaylist?.id"
            :save-disabled="!dailyPlaylist?.id || paletteLog.isPending(dailyPlaylist.id)"
            @play="handlePlayDailyPlaylist"
            @save="handleTogglePalette(dailyPlaylist)"
          />
        </div>

        <RouterLink
          class="daily-swatch"
          :to="playlistTo(dailyPlaylist.id)"
          :style="{ backgroundColor: dailyPlaylist.color_hex }"
          :aria-label="`${dailyPlaylist.color_name} 플레이리스트로 이동`"
        ></RouterLink>
      </div>
    </section>

    <!-- 2) Daily Spectrum -->
    <DailySpectrumPanel
      :is-daily-loading="isDailyLoading"
      :daily-error-message="dailyErrorMessage"
      :daily-playlist-id="dailyPlaylist?.id ?? null"
      :playlist-to="playlistTo"
      :is-saved="paletteLog.has"
      :is-palette-pending="paletteLog.isPending"
      @toggle-palette="handleTogglePalette"
    />

    <!-- 3) Palette Log -->
    <section class="panel log" style="--panel-delay: 96ms">
      <div class="panel-head">
        <h3>Palette Log</h3>
        <RouterLink class="more" to="/palette-log">더보기</RouterLink>
      </div>

      <div class="log-list">
        <div v-if="!paletteLogPreview.length" class="log-empty">
          <span>아직 저장된 팔레트로그가 없습니다</span>
          <RouterLink to="/color-chart" class="log-empty-cta">컬러차트 살펴보기</RouterLink>
        </div>
        <RouterLink
          v-for="log in paletteLogPreview"
          :key="`${log.playlist_id}-${log.created_at}`"
          class="log-item"
          :to="playlistTo(log.playlist_id)"
          :style="{ '--bg': log.playlist.color_hex }"
        >
          <div class="log-copy">
            <div class="log-top">{{ log.playlist.pantone_code }}</div>
            <div class="log-main">
              <strong>{{ log.playlist.color_name }}</strong>
            </div>
            <div class="log-sub">♫ {{ formatTrackCount(log.playlist.totalTracks) }} Tracks</div>
          </div>
          <span class="log-arrow" aria-hidden="true">
            <img src="@/assets/icons/arrow-right.svg" alt=">" />
          </span>
        </RouterLink>
      </div>
    </section>

    <!-- 4) Echo Notes -->
    <section class="panel echo" style="--panel-delay: 144ms">
      <div class="panel-head">
        <h3>Echo Notes<span class="dot" :style="echoDotStyle"></span></h3>
        <span class="hint">다른 사용자들의 한마디</span>
      </div>

      <div class="echo-card">
        <Transition name="echo-fade" mode="out-in">
          <p :key="currentEchoNote" class="echo-text">{{ currentEchoNote }}</p>
        </Transition>
        <div class="echo-line"></div>
        <p class="echo-date">{{ echoDateLabel }}</p>
      </div>
    </section>
  </main>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, nextTick, ref, watch } from 'vue';
import { usePaletteLogStore } from '@/stores/paletteLog';
import { usePlayerStore } from '@/stores/player';
import { apiRequest } from '@/services/httpClient';
import { playPlaylistFirstTrack } from '@/services/playlistService';
import PlaylistActionControls from '@/components/PlaylistActionControls.vue';
import DailySpectrumPanel from '@/components/DailySpectrumPanel.vue';
import { showAlert } from '@/utils/alert';

const paletteLog = usePaletteLogStore();
const player = usePlayerStore();
const rootEl = ref(null);
const dailyPlaylist = ref(null);
const isDailyLoading = ref(false);
const dailyErrorMessage = ref('');
const paletteLogPreview = computed(() => paletteLog.paletteLogs.slice(0, 4));
const isEnterReady = ref(false);
const typedDailyCount = ref(0);
const echoNotes = [
  '오늘은 좀 울적하다..ㅜㅜ',
  '오늘은 괜히 기분 좋음 ㅎㅎ',
  '아무 일 없는데 괜히 마음이 축 처짐...',
  '생각보다 평온해서 마음이 놓였다..',
  '오늘따라 말하기도 귀찮네 ㅋㅋ',
  '별거 없었는데 괜히 행복한 날!',
  '괜찮은 줄 알았는데 좀 지쳤나봐..',
  '그냥 오늘은 조용히 있고 싶다!!!!',
  '오늘은 마음이 좀 가벼워서 좋다',
  '별말 아닌데 괜히 계속 생각남..',
  '기분이 왔다갔다해서 더 피곤함 ㅜ',
  '소소한데 은근 웃을 일이 많았다 ㅋㅋ',
  '하루가 왜 이렇게 길지..',
  '괜히 센치해서 기록 남겨둠 ㅎㅎ',
  '오늘의 나는 좀 천천히 가고 싶음...'
];
const currentEchoNote = ref(echoNotes[0]);

let echoRotationTimer = null;
let dailyTypingTimer = null;
let dailyTypingDelayTimer = null;

/* ---------- 라우팅 헬퍼 ---------- */
// 지금은 임시로 /playlist?id=... 형태
// 라우터를 /playlist/:id 로 쓰면 return { name:'playlist', params:{ id } } 로 바꾸면 됨.
function playlistTo(id) {
  return {
    path: '/playlist',
    query: { id },
    state: { fromBottomTab: '/main' }
  };
}

async function handlePlayDailyPlaylist() {
  if (!dailyPlaylist.value?.id) return;

  try {
    await playPlaylistFirstTrack(player, dailyPlaylist.value.id, {
      autoplay: true,
      openMode: 'main'
    });
  } catch (error) {
    const message = error instanceof Error ? error.message : '플레이리스트 재생에 실패했습니다.';
    showAlert(message);
  }
}

async function handleTogglePalette(item) {
  try {
    await paletteLog.toggle(item?.id);
  } catch (error) {
    const message =
      error instanceof Error ? error.message : '팔레트 로그 저장 처리에 실패했습니다.';
    showAlert(message);
  }
}

async function loadDailyPlaylist() {
  isDailyLoading.value = true;
  dailyErrorMessage.value = '';
  dailyPlaylist.value = null;

  try {
    const result = await apiRequest(
      '/api/playlist/daily.php',
      {},
      '오늘의 톤을 불러오지 못했습니다.'
    );

    dailyPlaylist.value = result?.playlist ?? null;
  } catch (error) {
    dailyPlaylist.value = null;
    dailyErrorMessage.value =
      error instanceof Error ? error.message : '오늘의 톤을 불러오지 못했습니다.';
  } finally {
    isDailyLoading.value = false;
  }
}

/* ---------- color utils (hex/rgb/rgba 전부 처리) ---------- */
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

  const m = c.match(/rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)(?:\s*,\s*[\d.]+)?\s*\)/i);
  if (m) return { r: Number(m[1]), g: Number(m[2]), b: Number(m[3]) };

  return null;
}

function getBrightness(color) {
  const rgb = parseToRGB(color);
  if (!rgb) return 0;
  const { r, g, b } = rgb;
  return (r * 299 + g * 587 + b * 114) / 1000;
}

function formatTrackCount(value) {
  return Number(value || 0).toLocaleString('en-US');
}

function pickNextEchoNote() {
  if (echoNotes.length <= 1) {
    currentEchoNote.value = echoNotes[0] || '';
    return;
  }

  let nextNote = currentEchoNote.value;
  while (nextNote === currentEchoNote.value) {
    nextNote = echoNotes[Math.floor(Math.random() * echoNotes.length)];
  }

  currentEchoNote.value = nextNote;
}

function startEchoRotation() {
  pickNextEchoNote();
  echoRotationTimer = window.setInterval(() => {
    pickNextEchoNote();
  }, 4000);
}

function stopDailyTyping() {
  if (dailyTypingDelayTimer) {
    window.clearTimeout(dailyTypingDelayTimer);
    dailyTypingDelayTimer = null;
  }

  if (!dailyTypingTimer) return;
  window.clearInterval(dailyTypingTimer);
  dailyTypingTimer = null;
}

function startDailyTyping() {
  stopDailyTyping();
  typedDailyCount.value = 0;

  if (dailyIntroSource.value.totalLength < 1) return;

  dailyTypingDelayTimer = window.setTimeout(() => {
    dailyTypingDelayTimer = null;
    dailyTypingTimer = window.setInterval(() => {
      typedDailyCount.value += 1;

      if (typedDailyCount.value >= dailyIntroSource.value.totalLength) {
        typedDailyCount.value = dailyIntroSource.value.totalLength;
        stopDailyTyping();
      }
    }, 48);
  }, 320);
}

const echoDotStyle = computed(() => ({
  backgroundColor: dailyPlaylist.value?.color_hex || '#7b56d7'
}));

const echoDateLabel = computed(() => {
  const today = new Date();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  return `${month}.${day}`;
});

const dailyToneAccentStyle = computed(() => ({
  color: dailyPlaylist.value?.color_hex || '#615694'
}));

const dailyIntroSource = computed(() => {
  const prefix = '오늘의 톤은 ';
  const name = dailyPlaylist.value?.color_name || '';
  const suffix = ' 입니다.';
  const secondLine = '오늘의 톤에 맞는 플레이리스트를 감상해보세요!';

  return {
    prefix,
    name,
    suffix,
    secondLine,
    totalLength: prefix.length + name.length + suffix.length + secondLine.length
  };
});

const typedDailyIntro = computed(() => {
  const source = dailyIntroSource.value;
  let remaining = typedDailyCount.value;

  const prefix = source.prefix.slice(0, Math.max(remaining, 0));
  remaining -= source.prefix.length;

  const name = source.name.slice(0, Math.max(remaining, 0));
  remaining -= source.name.length;

  const suffix = source.suffix.slice(0, Math.max(remaining, 0));
  remaining -= source.suffix.length;

  const secondLine = source.secondLine.slice(0, Math.max(remaining, 0));

  return {
    prefix,
    name,
    suffix,
    secondLine
  };
});

/* ---------- Palette Log: 배경명도에 따라 글자색 자동 ---------- */
function applyLogItemTheme(root) {
  if (!root) return;

  const items = root.querySelectorAll('.log-item');
  items.forEach((item) => {
    let bg = item.style.getPropertyValue('--bg')?.trim();
    if (!bg) bg = getComputedStyle(item).getPropertyValue('--bg').trim();
    if (!bg) return;

    const brightness = getBrightness(bg);

    // ✅ 덮어쓰기 충돌 줄이려면 "class만" 쓰는 게 더 깔끔하지만,
    // 지금은 즉시 눈에 보이게 inline color도 같이 적용
    if (brightness > 170) {
      item.style.color = '#3f5f73';
      item.classList.add('is-light');
      item.classList.remove('is-dark');
    } else {
      item.style.color = '#F2F2EE';
      item.classList.add('is-dark');
      item.classList.remove('is-light');
    }
  });
}

onMounted(async () => {
  isEnterReady.value = false;
  startEchoRotation();
  await paletteLog.load({ silent: true });
  await loadDailyPlaylist();
  await nextTick();

  // 스타일 적용 타이밍 보장(2프레임)
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      applyLogItemTheme(rootEl.value);
      isEnterReady.value = true;
    });
  });
});

onBeforeUnmount(() => {
  stopDailyTyping();
  if (echoRotationTimer) {
    window.clearInterval(echoRotationTimer);
    echoRotationTimer = null;
  }
});

watch(
  paletteLogPreview,
  async () => {
    await nextTick();
    applyLogItemTheme(rootEl.value);
  },
  { deep: true }
);

watch(
  () => dailyPlaylist.value?.id,
  (playlistId) => {
    if (!playlistId) {
      stopDailyTyping();
      typedDailyCount.value = 0;
      return;
    }

    startDailyTyping();
  }
);
</script>

<style>
/* ===== Home layout ===== */
.home {
  padding: 0 25px;
  display: grid;
  gap: 35px;
}

.home .panel {
  opacity: 0;
  transform: translateY(12px);
  will-change: transform, opacity;
}

.home.is-enter-ready .panel {
  animation: main-panel-enter 320ms ease both;
  animation-delay: var(--panel-delay, 0ms);
}

/* 공통 패널(흰 박스) */
.panel {
  background: #fff;
  border-radius: 22px;
  padding: 16px;
  box-shadow:
    0 18px 40px rgba(0, 0, 0, 0.1),
    0 2px 10px rgba(0, 0, 0, 0.06);
}

/* ===== Daily tone ===== */
.daily {
  width: 100%;
  height: 196px;
  background: linear-gradient(135deg, #f2e3f3 0%, #fbf0d2 45%, #f2f6ff 100%);
}

.daily-pill {
  display: inline-flex;
  align-items: center;
  height: 26px;
  padding: 0 12px;
  border-radius: 999px;
  border: 1px solid #3f5f73;
  background: #ffffff;
  font-size: 10px;
  font-weight: 600;
  margin-bottom: 12px;
}

.daily-inner {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  column-gap: 12px;
  align-items: end;
}

.daily-text {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.daily-state {
  padding: 24px 0;
  font-size: 13px;
  color: #3f5f73;
  text-align: center;
}

.daily-state-error {
  color: #b42318;
}

.daily-text h2 {
  margin: 0 0 8px;
  font-size: 20px;
  font-weight: 700;
  color: #615694;
}

.daily-text p {
  margin: 0 0 12px;
  font-size: 12px;
  line-height: 1.45;
  font-weight: 500;
}

.daily-description {
  min-height: calc(12px * 1.45 * 2);
  word-break: keep-all;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.daily-text b {
  color: #615694;
  font-weight: 700;
}

.daily-actions {
  margin-top: auto;
  padding-top: 2px;
}

.daily-actions .playlist-action-controls__play {
  width: 44px;
  height: 44px;
  animation: daily-soft-heartbeat 2.6s ease-in-out infinite;
  transform-origin: center;
}

.daily-actions .playlist-action-controls__play-icon {
  width: 44px;
  height: 44px;
}

.daily-swatch {
  width: 60px;
  height: 100px;
  margin-left: auto;
  position: relative;
  overflow: hidden;
  align-self: flex-start;
  border-radius: 29.5px;
  background: #615694;
  border: 3px solid #ffffff;
  box-shadow: 0px 0px 4px 0px rgba(0, 0, 0, 0.25);
}

.daily-swatch::after {
  content: '';
  position: absolute;
  inset: -10% auto -10% -90%;
  width: 72%;
  background: linear-gradient(
    102deg,
    rgba(255, 255, 255, 0) 0%,
    rgba(255, 255, 255, 0.015) 18%,
    rgba(255, 255, 255, 0.08) 38%,
    rgba(255, 255, 255, 0.2) 50%,
    rgba(255, 255, 255, 0.08) 62%,
    rgba(255, 255, 255, 0.015) 82%,
    rgba(255, 255, 255, 0) 100%
  );
  transform: skewX(-40deg);
  filter: blur(2px);
  pointer-events: none;
  animation: daily-swatch-glass-sheen 4s ease infinite;
}

/* 패널 헤더 */
.panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
  position: relative;
  z-index: 2;
}

.panel h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  letter-spacing: -0.2px;
}

.hint,
.more {
  font-size: 10px;
  font-weight: 500;
  text-decoration: none;
  color: inherit;
}

/* ===== Palette Log ===== */
.log-list {
  display: grid;
  gap: 14px;
}

.log-empty {
  padding: 14px 0;
  font-size: 13px;
  color: var(--color-text-primary);
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.log-empty-cta {
  margin-top: 18px;
  border-radius: 999px;
  padding: 7px 12px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  border: 0;
  background: var(--color-primary);
  color: #ffffff;
  text-decoration: none;
}

.log-item {
  position: relative;
  display: block;
  border-radius: 18px;
  padding: 14px 52px 14px 16px;
  background: var(--bg, #b7cc1a);
  color: #fff;
  text-decoration: none;
  box-shadow: 0 18px 30px rgba(0, 0, 0, 0.18);
}

.log-copy {
  min-width: 0;
}

.log-top {
  font-size: 10px;
  font-weight: 700;
  opacity: 0.9;
}

.log-main {
  margin-top: 4px;
}

.log-main strong {
  display: inline-block;
  font-size: 26px;
  font-weight: 700;
  letter-spacing: -0.6px;
  line-height: 1;
  text-decoration: underline;
  text-underline-offset: 5px;
}

.log-arrow {
  position: absolute;
  top: 50%;
  right: 16px;
  transform: translateY(-50%);
  width: 18px;
  height: 18px;
  opacity: 0.55;
  display: flex;
  align-items: center;
  justify-content: center;
}

.log-arrow img {
  width: 18px;
  height: 18px;
  display: block;
}

.log-item.is-dark .log-arrow img {
  filter: brightness(0) invert(1);
}

.log-item.is-light .log-arrow img {
  filter: none;
}

.log-sub {
  margin-top: 6px;
  font-size: 12px;
  font-weight: 900;
  opacity: 0.9;
}

/* ===== Echo Notes ===== */
.dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  background: #7b56d7;
  display: inline-block;
  margin-left: 8px;
}

.echo-card {
  text-align: center;
}

.echo-text {
  font-size: 18px;
  font-weight: 600;
  line-height: 1.4;
  min-height: calc(18px * 1.4 * 2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.echo-line {
  height: 1px;
  margin: 0 12px 10px;
}

.echo-date {
  margin: 0;
  font-size: 10px;
  font-weight: 600;
}

.echo-fade-enter-active,
.echo-fade-leave-active {
  transition:
    opacity 0.35s ease,
    transform 0.35s ease;
}

.echo-fade-enter-from,
.echo-fade-leave-to {
  opacity: 0;
  transform: translateY(4px);
}

@keyframes main-panel-enter {
  from {
    opacity: 0;
    transform: translateY(12px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes daily-soft-heartbeat {
  0%,
  100% {
    transform: scale(1);
  }

  8% {
    transform: scale(1.06);
  }

  15% {
    transform: scale(1);
  }

  22% {
    transform: scale(1.04);
  }

  30% {
    transform: scale(1);
  }
}

@keyframes daily-swatch-glass-sheen {
  0%,
  18%,
  100% {
    transform: translateX(-18%) skewX(-40deg);
    opacity: 0;
  }

  24% {
    opacity: 0.72;
  }

  48% {
    transform: translateX(310%) skewX(-40deg);
    opacity: 0.76;
  }

  56% {
    transform: translateX(345%) skewX(-40deg);
    opacity: 0;
  }
}
</style>
