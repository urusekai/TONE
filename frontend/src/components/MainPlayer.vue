<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import MarqueeText from '@/components/MarqueeText.vue';
import { usePlayerStore } from '@/stores/player';
import { usePaletteLogStore } from '@/stores/paletteLog';
import { apiRequest } from '@/services/httpClient';

import arrowDown from '@/assets/icons/arrow-down.svg';
import arrowDownLight from '@/assets/icons/arrow-down_light.svg';
import likeIcon from '@/assets/icons/like.svg';
import likeFullIcon from '@/assets/icons/like_full.svg';
import addIcon from '@/assets/icons/add.svg';
import addCompleteIcon from '@/assets/icons/addComplete.svg';
import shuffleIcon from '@/assets/icons/shuffle.svg';
import repeatIcon from '@/assets/icons/repeat.svg';
import repeatOneIcon from '@/assets/icons/repeat-one.svg';

const player = usePlayerStore();
const paletteLog = usePaletteLogStore();
const isMV = ref(false);
const videoRef = ref(null);
const isLikeSubmitting = ref(false);

const track = computed(() => {
  const t = player.current_track ?? {};
  return {
    title: t.title ?? '',
    artist: t.artist ?? '',
    cover_url: t.cover_url ?? '',
    color_name: t.color_name ?? '',
    pantone_code: t.pantone_code ?? '',
    color_hex: t.color_hex ?? '#D5E1E8',
    video_url: t.video_url ?? '',
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

function syncVideoTime() {
  const video = videoRef.value;
  if (!video || !track.value.video_url) return;

  const targetTime = Math.max(0, Number(player.current_time) || 0);
  if (!Number.isFinite(video.duration) || video.duration <= 0) {
    video.currentTime = targetTime;
    return;
  }

  const normalizedTime = targetTime % video.duration;
  if (Math.abs(video.currentTime - normalizedTime) > 1.5) {
    video.currentTime = normalizedTime;
  }
}

async function syncVideoPlayback() {
  const video = videoRef.value;
  if (!video || !isMV.value || !track.value.video_url) return;

  syncVideoTime();

  if (player.is_playing) {
    try {
      await video.play();
    } catch {
      // 자동재생 제한이 걸려도 오디오 재생은 유지
    }
    return;
  }

  video.pause();
}

watch(
  () => player.isMain,
  (open) => {
    if (open) {
      isMV.value = false;
      return;
    }

    const video = videoRef.value;
    if (video) video.pause();
  }
);

watch(
  () => [isMV.value, player.is_playing, track.value.video_url, player.current_track.id],
  async () => {
    await nextTick();
    await syncVideoPlayback();
  }
);

watch(
  () => player.current_time,
  () => {
    if (!isMV.value) return;
    syncVideoTime();
  }
);

watch(
  () => player.current_track.id,
  () => {
    isMV.value = false;
  }
);

function isBright(hex) {
  const c = String(hex || '')
    .replace('#', '')
    .trim();

  if (c.length === 3) {
    const expanded = c
      .split('')
      .map((ch) => ch + ch)
      .join('');
    return isBright(`#${expanded}`);
  }

  if (c.length !== 6) return false;

  const r = parseInt(c.slice(0, 2), 16);
  const g = parseInt(c.slice(2, 4), 16);
  const b = parseInt(c.slice(4, 6), 16);

  const brightness = (r * 299 + g * 587 + b * 114) / 1000;
  return brightness > 170;
}

const isDarkTone = computed(() => !isBright(track.value.color_hex));

const toneText = computed(() => (isDarkTone.value ? '#F2F2EE' : '#3F5F73'));
const toneTextSub = computed(() =>
  isDarkTone.value ? 'rgba(242,242,238,0.78)' : 'rgba(63,95,115,0.75)'
);
const toneDotOff = computed(() =>
  isDarkTone.value ? 'rgba(242,242,238,0.78)' : 'rgba(63,95,115,0.75)'
);
const toneDotOn = computed(() => (isDarkTone.value ? 'rgba(242,242,238,1)' : 'rgba(63,95,115,1)'));

const iconArrow = computed(() => (isDarkTone.value ? arrowDownLight : arrowDown));
const toneVars = computed(() => ({
  '--tone-text': toneText.value,
  '--tone-sub': toneTextSub.value,
  '--tone-dot-off': toneDotOff.value,
  '--tone-dot-on': toneDotOn.value
}));

const progressStyle = computed(() => ({
  '--progress': `${player.progress_percent}%`
}));

const isPlaying = computed(() => player.is_playing);
const displayColorName = computed(() => track.value.color_name || 'Now Playing');
const displayTitle = computed(() => track.value.title || '곡을 선택해 재생하세요');
const displayArtist = computed(() => track.value.artist || '플레이리스트에서 트랙을 선택하면 재생됩니다');
const playlistId = computed(() => String(player.current_playlist.id || '').trim());
const isLiked = computed(() => Boolean(player.current_playlist.liked));
const isSaved = computed(() => Boolean(player.current_playlist.saved));
const isSavePending = computed(() => (playlistId.value ? paletteLog.isPending(playlistId.value) : false));
const repeatButtonIcon = computed(() => (player.is_repeat_one ? repeatOneIcon : repeatIcon));

function formatTime(seconds) {
  const safe = Number.isFinite(seconds) ? Math.max(0, Math.floor(seconds)) : 0;
  const minute = Math.floor(safe / 60);
  const second = safe % 60;
  return `${String(minute).padStart(2, '0')}:${String(second).padStart(2, '0')}`;
}

function handleSeek(event) {
  const target = event.currentTarget;
  if (!target || player.duration <= 0) return;

  const rect = target.getBoundingClientRect();
  const ratio = rect.width > 0 ? (event.clientX - rect.left) / rect.width : 0;
  player.seekToRatio(ratio);
}

function handleVideoLoadedMetadata() {
  syncVideoTime();
}

async function handleToggleLike() {
  if (!playlistId.value || isLikeSubmitting.value) return;

  isLikeSubmitting.value = true;

  try {
    const result = await apiRequest(
      '/api/playlist/like.php',
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          playlist_id: Number(playlistId.value)
        })
      },
      '좋아요 처리에 실패했습니다.'
    );

    player.patchCurrentPlaylist({
      liked: Boolean(result?.liked),
      like_count: Number(result?.like_count || 0)
    });
  } catch (error) {
    const message = error instanceof Error ? error.message : '좋아요 처리에 실패했습니다.';
    window.alert(message);
  } finally {
    isLikeSubmitting.value = false;
  }
}

async function handleToggleSave() {
  if (!playlistId.value || isSavePending.value) return;

  try {
    const result = await paletteLog.toggle(playlistId.value);
    if (!result) return;

    player.patchCurrentPlaylist({
      saved: Boolean(result?.saved)
    });
  } catch (error) {
    const message = error instanceof Error ? error.message : '팔레트 로그 저장 처리에 실패했습니다.';
    window.alert(message);
  }
}
</script>

<template>
  <section class="main-player" :class="{ 'is-active': player.isMain }">
    <div class="main-player__media" :class="{ 'is-mv': isMV }" :style="toneVars">
      <header
        class="main-player__header"
        :class="{ 'is-mv': isMV }"
        :style="{ background: track.color_hex }"
      >
        <button type="button" class="main-player__back-btn" @click="handleCloseMain">
          <img :src="iconArrow" alt="메인플레이어 닫기" />
        </button>

        <div class="main-player__header-center" :class="{ 'is-active': isMV, 'is-empty': !isMV }">
          <p class="main-player__header-label">PLAYLIST</p>
          <p class="main-player__header-name">{{ displayColorName }}</p>
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

      <button v-if="isMV" type="button" class="main-player__mv" @click="toggleMV">
        <video
          v-if="track.video_url"
          ref="videoRef"
          :key="track.video_url || track.id"
          class="main-player__mv-video"
          :src="track.video_url"
          autoplay
          muted
          loop
          preload="metadata"
          playsinline
          @loadedmetadata="handleVideoLoadedMetadata"
        ></video>
        <div v-else class="main-player__mv-empty">
          <img
            v-if="track.cover_url"
            :src="track.cover_url"
            alt="앨범커버"
            class="main-player__mv-placeholder"
          />
        </div>
      </button>

      <div v-else class="main-player__tone" :style="{ background: track.color_hex }">
        <div class="main-player__tone-meta" :class="{ 'is-hidden': isMV }">
          <p class="main-player__color-name">{{ displayColorName }}</p>
          <p class="main-player__color-code">{{ track.pantone_code }}</p>
        </div>

        <button
          type="button"
          class="main-player__cover-circle-btn"
          @click="toggleMV"
          aria-label="뮤직비디오 보기"
        >
          <img
            v-if="track.cover_url"
            :src="track.cover_url"
            alt="앨범커버"
            class="main-player__cover-circle"
          />
        </button>
      </div>
    </div>

    <div class="main-player__info">
      <MarqueeText
        tag="p"
        class="main-player__title"
        :class="{ 'is-empty': !player.has_track }"
        :text="displayTitle"
        align="center"
        :speed="52"
        :gap="40"
      />
      <p class="main-player__artist" :class="{ 'is-empty': !player.has_track }">{{ displayArtist }}</p>
    </div>

    <div class="main-player__progress" @click="handleSeek">
      <div class="main-player__progress-cover" :style="progressStyle"></div>
    </div>

    <div class="main-player__time">
      <span>{{ formatTime(player.current_time) }}</span>
      <span>{{ formatTime(player.duration) }}</span>
    </div>

    <div class="main-player__actions-top">
      <button
        type="button"
        class="main-player__prev-btn"
        :disabled="!player.has_track || !player.has_prev"
        @click="player.playPrev"
      >
        <img src="@/assets/icons/prev-song.svg" alt="이전곡" />
      </button>
      <button
        type="button"
        class="main-player__play-toggle-btn"
        :disabled="!player.has_track"
        :class="{ 'is-playing': isPlaying }"
        @click="player.togglePlay"
      >
        <img src="@/assets/icons/pause-circle.svg" alt="일시정지" />
        <img src="@/assets/icons/play-circle.svg" alt="재생" />
      </button>
      <button
        type="button"
        class="main-player__next-btn"
        :disabled="!player.has_track || (!player.has_next && !player.is_repeat && !player.is_shuffle)"
        @click="player.playNext"
      >
        <img src="@/assets/icons/next-song.svg" alt="다음곡" />
      </button>
    </div>

    <div class="main-player__actions-bottom">
      <button
        type="button"
        class="main-player__like-btn"
        :class="{ 'is-liked': isLiked }"
        :disabled="!playlistId || isLikeSubmitting"
        @click="handleToggleLike"
      >
        <img :src="likeIcon" alt="좋아요" />
        <img :src="likeFullIcon" alt="좋아요" />
      </button>
      <button
        type="button"
        class="main-player__shuffle-btn"
        :class="{ 'is-active': player.is_shuffle }"
        :disabled="!player.has_track"
        @click="player.toggleShuffle"
      >
        <img :src="shuffleIcon" alt="셔플" />
      </button>
      <button
        type="button"
        class="main-player__repeat-btn"
        :class="{ 'is-active': player.is_repeat }"
        :disabled="!player.has_track"
        @click="player.toggleRepeat"
      >
        <img :src="repeatButtonIcon" alt="반복" />
      </button>
      <button
        type="button"
        class="main-player__add-btn"
        :disabled="!playlistId || isSavePending"
        @click="handleToggleSave"
      >
        <img :src="isSaved ? addCompleteIcon : addIcon" alt="추가" />
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
  border: 0;
  padding: 0;
  display: block;
  cursor: pointer;
}

.main-player__mv-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.main-player__mv-empty {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background:
    radial-gradient(circle at top, rgba(255, 255, 255, 0.08), transparent 45%),
    linear-gradient(180deg, #1d2227 0%, #101316 100%);
}

.main-player__mv-placeholder {
  width: 140px;
  height: 140px;
  border-radius: 999px;
  object-fit: cover;
  border: 4px solid rgba(255, 255, 255, 0.9);
  box-shadow: 0 0 24px rgba(0, 0, 0, 0.3);
}

/* ==================================================
   이하 기존 UI
================================================== */
.main-player__info {
  text-align: center;
  margin-top: 26px;
  overflow: hidden;
}

.main-player__title {
  width: 100%;
  font-size: 36px;
  font-weight: 700;
  margin: 0;
  color: #3f5f73;
  line-height: 1.1;
}

.main-player__artist {
  margin: 6px 0 0;
  font-size: 20px;
  font-weight: 500;
}

.main-player__artist.is-empty {
  color: var(--color-text-secondary);
}

.main-player__progress {
  position: relative;
  width: 100%;
  height: 5px;
  margin-top: auto;
  border-radius: 999px;
  overflow: hidden;
  background: linear-gradient(90deg, #a8d4e6 0%, #c3b7d6 49%, #f5c9c6 100%);
  cursor: pointer;
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

.main-player__play-toggle-btn:disabled,
.main-player__prev-btn:disabled,
.main-player__next-btn:disabled {
  opacity: 0.45;
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

.main-player__actions-bottom button:disabled {
  opacity: 0.45;
}

.main-player__shuffle-btn,
.main-player__repeat-btn {
  transition: opacity 0.2s ease;
}

.main-player__shuffle-btn img,
.main-player__repeat-btn img {
  opacity: 0.4;
  transition: opacity 0.2s ease;
}

.main-player__shuffle-btn.is-active,
.main-player__repeat-btn.is-active {
  opacity: 1;
}

.main-player__shuffle-btn.is-active img,
.main-player__repeat-btn.is-active img {
  opacity: 1;
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
