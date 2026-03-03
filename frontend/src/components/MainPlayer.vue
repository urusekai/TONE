<script setup>
import { usePlayerStore } from '@/stores/player';

const player = usePlayerStore();

function handleCloseMain() {
  player.closeMain();
}
</script>

<template>
  <section class="main-player" :class="{ 'is-active': player.isMain }">
    <header class="main-player__header">
      <button type="button" class="main-player__back-btn" @click="handleCloseMain">
        <img src="@/assets/icons/arrow-down.svg" alt="메인플레이어 닫기" />
      </button>
      <div class="main-player__header-info">
        <p class="main-player__header-label">PLAYLIST</p>
        <p class="main-player__header-name">Color Name</p>
      </div>
      <button type="button" class="main-player__profile-btn" aria-label="프로필">
        <span class="main-player__profile-avatar"></span>
      </button>
    </header>
    <img :src="player.currentTrack.cover" alt="앨범커버" class="main-player__cover" />
    <div class="main-player__info">
      <p class="main-player__title">{{ player.currentTrack.title }}</p>
      <p class="main-player__artist">{{ player.currentTrack.artist }}</p>
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
        <img src="@/assets/icons/add.svg" alt="좋아요" />
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

/* 메인 플레이어 - 헤더 */
.main-player__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 25px 0;
  margin-bottom: 10px;
}

.main-player__back-btn,
.main-player__profile-btn {
  width: 34px;
  height: 34px;
  flex: none;
  display: flex;
  align-items: center;
  justify-content: center;
}

.main-player__back-btn {
  justify-content: flex-start;
}

.main-player__back-btn img {
  width: 20px;
  height: 20px;
}

.main-player__header-info {
  flex: 1;
  text-align: center;
}

.main-player__header-label {
  color: var(--color-text-secondary);
  font-size: 12px;
  font-weight: 700;
  line-height: 1;
}

.main-player__header-name {
  color: var(--color-text-primary);
  font-size: 16px;
  font-weight: 700;
  line-height: 1;
}

.main-player__profile-avatar {
  display: block;
  width: 34px;
  height: 34px;
  border-radius: 999px;
  background-color: var(--color-pantone-primary);
}

/* 메인 플레이어 - 커버/정보 */
.main-player__cover {
  width: 100%;
  aspect-ratio: 1 / 1;
  object-fit: cover;
  border: 6px solid #71acd8;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.16);
}

.main-player__info {
  margin-top: auto;
  text-align: center;
}

.main-player__title {
  color: var(--color-text-primary);
  font-size: 26px;
  font-weight: 700;
  line-height: 1.2;
}

.main-player__artist {
  margin-top: 6px;
  color: var(--color-text-primary);
  font-size: 16px;
  font-weight: 600;
}

/* 메인 플레이어 - 진행바 */
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

/* 메인 플레이어 - 컨트롤 */
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
</style>
