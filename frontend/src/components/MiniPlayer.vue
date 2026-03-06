<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { usePlayerStore } from '@/stores/player';
import handleIcon from '@/assets/icons/handle.svg';

const player = usePlayerStore();

const VISIBLE_PEEK = 22;
const COMMIT_RATIO = 0.55;
const IGNORE_CLICK_MS = 280;
const DRAG_GUARD_PX = 3;

const surfaceRef = ref(null);
const travelOffset = ref(74);
const dragOffset = ref(0);
const isDragging = ref(false);

let pointerId = null;
let startY = 0;
let startOffset = 0;
let ignoreClickUntil = 0;
let resizeObserver = null;

function handleOpenMain() {
  if (!player.has_track) return;
  player.openMain();
}

function clamp(value, min, max) {
  return Math.min(max, Math.max(min, value));
}

function currentOffset() {
  return isDragging.value ? dragOffset.value : player.isHidden ? travelOffset.value : 0;
}

function measureTravelOffset() {
  if (!surfaceRef.value) return;

  const fullHeight = surfaceRef.value.offsetHeight;
  travelOffset.value = Math.max(0, fullHeight - VISIBLE_PEEK);

  if (dragOffset.value > travelOffset.value) {
    dragOffset.value = travelOffset.value;
  }
}

function canStartDrag(event) {
  if (!player.isMini && !player.isHidden) return false;
  if (event.pointerType === 'mouse' && event.button !== 0) return false;
  return true;
}

function markIgnoreClick() {
  ignoreClickUntil = Date.now() + IGNORE_CLICK_MS;
}

function isClickGuardActive() {
  return Date.now() < ignoreClickUntil;
}

function handleGrabPointerDown(event) {
  if (!canStartDrag(event)) return;

  pointerId = event.pointerId;
  startY = event.clientY;
  startOffset = currentOffset();
  dragOffset.value = startOffset;
  isDragging.value = true;

  event.currentTarget?.setPointerCapture?.(event.pointerId);

  if (event.cancelable) {
    event.preventDefault();
  }
}

function handleGrabPointerMove(event) {
  if (!isDragging.value || event.pointerId !== pointerId) return;

  const deltaY = event.clientY - startY;
  dragOffset.value = clamp(startOffset + deltaY, 0, travelOffset.value);

  if (event.cancelable) {
    event.preventDefault();
  }
}

function finishDrag(isCanceled = false) {
  if (!isDragging.value) return;

  const movedDistance = Math.abs(dragOffset.value - startOffset);
  if (movedDistance > DRAG_GUARD_PX) {
    markIgnoreClick();
  }

  if (!isCanceled) {
    if (dragOffset.value >= travelOffset.value * COMMIT_RATIO) {
      player.closeAll();
    } else {
      player.openMini();
    }
  }

  isDragging.value = false;
  dragOffset.value = 0;
  pointerId = null;
}

function handleGrabPointerUp(event) {
  if (event.pointerId !== pointerId) return;
  finishDrag(false);
}

function handleGrabPointerCancel(event) {
  if (event.pointerId !== pointerId) return;
  finishDrag(true);
}

function handleGrabLostCapture(event) {
  if (event.pointerId !== pointerId) return;
  finishDrag(false);
}

function handleSurfaceClick() {
  if (!player.isHidden) return;
  if (isClickGuardActive()) return;
  player.openMini();
}

function handleSurfaceKeyOpen() {
  if (!player.isHidden) return;
  if (isClickGuardActive()) return;
  player.openMini();
}

onMounted(() => {
  measureTravelOffset();

  resizeObserver = new ResizeObserver(() => {
    measureTravelOffset();
  });

  if (surfaceRef.value) {
    resizeObserver.observe(surfaceRef.value);
  }
});

onBeforeUnmount(() => {
  resizeObserver?.disconnect();
  resizeObserver = null;
});

const surfaceOffset = computed(() => {
  if (isDragging.value) {
    return dragOffset.value;
  }

  return player.isHidden ? travelOffset.value : 0;
});

const surfaceStyle = computed(() => ({
  transform: `translate(-50%, ${surfaceOffset.value}px)`
}));

const progressStyle = computed(() => ({
  '--progress': `${player.progress_percent}%`
}));

const displayTitle = computed(() => player.current_track.title || '곡을 선택해 재생하세요');
const displayArtist = computed(() => player.current_track.artist || '플레이리스트에서 트랙을 선택하세요');

function handleSeek(event) {
  const target = event.currentTarget;
  if (!target || player.duration <= 0) return;

  const rect = target.getBoundingClientRect();
  const ratio = rect.width > 0 ? (event.clientX - rect.left) / rect.width : 0;
  player.seekToRatio(ratio);
}
</script>

<template>
  <section
    ref="surfaceRef"
    class="mini-player"
    :class="{ 'is-hidden': player.isHidden, 'is-dragging': isDragging }"
    :style="surfaceStyle"
    :role="player.isHidden ? 'button' : null"
    :tabindex="player.isHidden ? 0 : null"
    :aria-label="player.isHidden ? '미니플레이어 열기' : null"
    @click="handleSurfaceClick"
    @keydown.enter.prevent="handleSurfaceKeyOpen"
    @keydown.space.prevent="handleSurfaceKeyOpen"
  >
    <div
      class="mini-handle-wrap"
      aria-hidden="true"
      @pointerdown="handleGrabPointerDown"
      @pointermove="handleGrabPointerMove"
      @pointerup="handleGrabPointerUp"
      @pointercancel="handleGrabPointerCancel"
      @lostpointercapture="handleGrabLostCapture"
    >
      <img class="mini-handle-icon" :src="handleIcon" alt="" />
    </div>

    <div class="mini-content">
      <button type="button" class="mini-thumb" :disabled="!player.has_track" @click="handleOpenMain">
        <img :src="player.current_track.cover_url" alt="앨범 커버" />
      </button>
      <div class="mini-body">
        <div class="mini-top">
          <p class="mini-title" :class="{ 'is-empty': !player.has_track }">{{ displayTitle }}</p>
          <div class="mini-actions">
            <button type="button" class="mini-btn">
              <img src="@/assets/icons/like.svg" alt="좋아요" class="icon-like" />
            </button>
            <button
              type="button"
              class="mini-btn"
              :disabled="!player.has_track"
              :class="{ 'is-playing': player.is_playing }"
              @click.stop="player.togglePlay"
            >
              <img src="@/assets/icons/play.svg" alt="재생" class="icon-play" />
              <img src="@/assets/icons/pause.svg" alt="재생일시정지" class="icon-pause" />
            </button>
          </div>
        </div>
        <p class="mini-artist" :class="{ 'is-empty': !player.has_track }">{{ displayArtist }}</p>
        <div class="mini-progress" @click="handleSeek">
          <div class="mini-progress-cover" :style="progressStyle"></div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.mini-player {
  position: fixed;
  left: 50%;
  transform: translate(-50%, 0);
  width: 100%;
  max-width: 402px;
  z-index: 1001;

  bottom: 75px;
  /* 하단바 높이에 맞춰 조절 */
  background: #ffffff;
  border-radius: 20px 20px 0 0;
  padding: 0 var(--layout-x) 0;

  display: flex;
  flex-direction: column;
  gap: 0;
  box-shadow: 0 -3px 10px rgba(0, 0, 0, 0.25);
  transition: transform 0.22s ease;
}

.mini-player.is-hidden {
  cursor: pointer;
}

.mini-player.is-dragging {
  transition: none;
}

.mini-handle-wrap {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 10px 0;
  touch-action: none;
  cursor: grab;
  line-height: 1;
}

.mini-player.is-dragging .mini-handle-wrap {
  cursor: grabbing;
}

.mini-handle-icon {
  width: 28px;
  height: 6px;
  opacity: 1;
  user-select: none;
}

.mini-content {
  display: flex;
  gap: 15px;
}

.mini-thumb {
  width: 60px;
  height: 60px;
  border-radius: 999px;
  overflow: hidden;
  display: flex;
}

.mini-thumb:disabled {
  cursor: default;
}

.mini-thumb img {
  width: 100%;
  height: 100%;
}

.mini-body {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.mini-top {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.mini-title {
  font-size: 20px;
  font-weight: 700;
  line-height: 1;
}

.mini-artist.is-empty {
  color: var(--color-text-secondary);
}

.mini-actions {
  display: flex;
  gap: 15px;
}

.icon-like {
  width: 20px;
  height: 20px;
}

.icon-play {
  width: 20px;
  height: 18px;
}

.icon-pause {
  width: 20px;
  height: 20px;
}

.icon-pause {
  display: none;
}

.mini-btn.is-playing .icon-play {
  display: none;
}

.mini-btn.is-playing .icon-pause {
  display: block;
}

.mini-artist {
  color: var(--color-text-secondary);
  font-size: 14px;
}

.mini-progress {
  position: relative;
  width: 100%;
  height: 5px;
  border-radius: 999px;
  overflow: hidden;
  background: linear-gradient(90deg, #a8d4e6 0%, #c3b7d6 49%, #f5c9c6 100%);
  margin-top: auto;
  margin-bottom: 2px;
  cursor: pointer;
}

.mini-btn:disabled {
  opacity: 0.45;
}

.mini-progress-cover {
  position: absolute;
  top: 0;
  right: 0;
  height: 100%;

  /* 진행률만큼 왼쪽 남기고 오른쪽 덮기 */
  width: calc(100% - var(--progress, 0%));
  background: #e5e6e5;

  /* 애니메이션 대응 */
  transition: width 0.2s linear;
}
</style>
