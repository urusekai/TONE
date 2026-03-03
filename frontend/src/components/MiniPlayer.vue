<script setup>
import { ref } from 'vue';
import { usePlayerStore } from '@/stores/player';

const player = usePlayerStore();
const miniPlayerRef = ref(null);

const CLOSE_SWIPE_THRESHOLD = 70;

let startY = 0;
let isSwipingDownOnMini = false;

function handleOpenMain() {
  player.openMain();
}

function handleCloseMini() {
  player.closeAll();
}

function handleMiniTouchStart(event) {
  startY = event.touches[0].clientY;
  isSwipingDownOnMini = true;
}

function handleMiniTouchMove(event) {
  if (!isSwipingDownOnMini) return;

  const currentY = event.touches[0].clientY;
  const deltaY = currentY - startY;

  if (deltaY <= 0 || !miniPlayerRef.value) return;

  miniPlayerRef.value.style.transform = `translate(-50%, ${Math.min(deltaY, 140)}px)`;
}

function handleMiniTouchEnd(event) {
  if (!isSwipingDownOnMini) return;
  isSwipingDownOnMini = false;

  const endY = event.changedTouches[0].clientY;
  const deltaY = endY - startY;

  if (miniPlayerRef.value) {
    miniPlayerRef.value.style.transform = '';
  }

  if (deltaY > CLOSE_SWIPE_THRESHOLD) {
    player.closeAll();
  }
}
</script>

<template>
  <section
    ref="miniPlayerRef"
    class="mini-player"
    @touchstart.passive="handleMiniTouchStart"
    @touchmove.passive="handleMiniTouchMove"
    @touchend="handleMiniTouchEnd"
  >
    <button type="button" class="mini-thumb" @click="handleOpenMain">
      <img :src="player.currentTrack.cover" alt="앨범 커버" />
    </button>
    <div class="mini-body">
      <div class="mini-top">
        <p class="mini-title">{{ player.currentTrack.title }}</p>
        <div class="mini-actions">
          <button type="button" class="mini-btn">
            <img src="@/assets/icons/like.svg" alt="좋아요" />
          </button>
          <button type="button" class="mini-btn is-playing">
            <img src="@/assets/icons/play.svg" alt="재생" class="icon-play" />
            <img src="@/assets/icons/pause.svg" alt="재생일시정지" class="icon-pause" />
          </button>
          <button type="button" class="mini-btn mini-btn--close" @click="handleCloseMini">
            <img src="@/assets/icons/close.svg" alt="닫기" class="icon-close" />
          </button>
        </div>
      </div>
      <p class="mini-artist">{{ player.currentTrack.artist }}</p>
      <div class="mini-progress">
        <div class="mini-progress-cover" style="--progress: 65%"></div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.mini-player {
  position: fixed;
  left: 50%;
  transform: translateX(-50%);
  width: 100%;
  max-width: 402px;
  z-index: 1001;

  bottom: 75px;
  /* 하단바 높이에 맞춰 조절 */
  background: #ffffff;
  border-radius: 20px 20px 0 0;
  padding: 25px var(--layout-x);

  display: flex;
  gap: 15px;
  box-shadow: 0 -3px 10px rgba(0, 0, 0, 0.25);
  transition: transform 0.2s ease;
}

.mini-thumb {
  width: 60px;
  height: 60px;
  border-radius: 999px;
  overflow: hidden;
  display: flex;
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

.mini-actions {
  display: flex;
  gap: 15px;
}

.icon-play {
  width: 20px;
  height: 18px;
}

.icon-pause,
.icon-close {
  width: 20px;
  height: 20px;
}

.icon-close {
  width: 12px;
  height: 12px;
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
