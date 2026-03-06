<script setup>
import { usePlayerStore } from '@/stores/player';
import heroThumbImage from '@/assets/images/thumb-color.png';
import trackThumbImage from '@/assets/images/thumb.png';
import likeIcon from '@/assets/icons/like.svg';
import playCircleIcon from '@/assets/icons/play-circle.svg';
import addIcon from '@/assets/icons/add.svg';

const player = usePlayerStore();

const trackList = Array.from({ length: 11 }, (_, index) => ({
  id: index + 1,
  title: 'Falling Behind',
  artist: 'Laufey',
  cover: trackThumbImage
}));

function handleOpenMainPlayer(track) {
  player.openMain(track);
}

function handleOpenFirstTrack() {
  if (!trackList.length) return;
  player.openMain(trackList[0]);
}
</script>

<template>
  <main id="playlist">
    <section class="playlist-hero">
      <img class="playlist-hero__thumb" :src="heroThumbImage" alt="썸네일" />
      <div class="playlist-hero__content">
        <div class="playlist-hero__text">
          <p class="playlist-hero__title">Pale Dogwood</p>
          <p class="playlist-hero__code">12-3456</p>
        </div>
        <div class="playlist-hero__actions">
          <div class="playlist-hero__likes">
            <img :src="likeIcon" alt="좋아요" />
            <span>12,300</span>
          </div>
          <button type="button" class="playlist-hero__play-button" @click="handleOpenFirstTrack">
            <img class="playlist-hero__add-icon" :src="addIcon" alt="추가" />
            <span class="playlist-hero__play-circle">
              <img :src="playCircleIcon" alt="재생" />
            </span>
          </button>
        </div>
      </div>
    </section>

    <section class="playlist-tracks">
      <ul class="playlist-tracks__list">
        <li
          v-for="track in trackList"
          :key="track.id"
          class="playlist-track-item"
          role="button"
          tabindex="0"
          @click="handleOpenMainPlayer(track)"
          @keydown.enter.prevent="handleOpenMainPlayer(track)"
          @keydown.space.prevent="handleOpenMainPlayer(track)"
        >
          <img class="playlist-track-item__thumb" :src="track.cover" alt="썸네일" />
          <div class="playlist-track-item__meta">
            <p class="playlist-track-item__title">{{ track.title }}</p>
            <p class="playlist-track-item__artist">{{ track.artist }}</p>
          </div>
        </li>
      </ul>
    </section>
  </main>
</template>

<style scoped>
#playlist {
  --app-main-bottom-gap: 0px;
  --playlist-main-side-padding: 25px;
  width: 100%;
  min-height: 0;
  justify-content: flex-start;
  align-items: stretch;
  overflow: hidden;
  padding-bottom: 0;
}

#playlist .playlist-hero {
  display: flex;
  flex: 0 0 auto;
  width: auto;
  gap: 20px;
  margin-inline: calc(var(--playlist-main-side-padding) * -1);
  padding: 0 var(--playlist-main-side-padding) 25px;
  box-shadow: 0 10px 12px -12px rgba(0, 0, 0, 0.45);
}

#playlist .playlist-hero__thumb {
  border-radius: 17px;
  border: 3px solid white;
  width: 100px;
  height: 100px;
  box-shadow: 0 0 4px rgba(0, 0, 0, 0.25);
}

#playlist .playlist-hero__content {
  width: 100%;
  padding: 5px 0;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

#playlist .playlist-hero__title {
  font-size: 26px;
  font-weight: bold;
}

#playlist .playlist-hero__code {
  font-size: 16px;
  font-weight: bold;
}

#playlist .playlist-hero__actions {
  display: flex;
  justify-content: space-between;
}

#playlist .playlist-hero__likes {
  margin-top: auto;
  padding-bottom: 3px;
  display: flex;
  gap: 3px;
}

/* 재생버튼 */
#playlist .playlist-hero__play-button {
  width: auto;
  height: 36px;
  border-radius: 50px;
  padding-left: 15px;
  background: #f2f2ee;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  box-shadow: 0 0 4px inset rgba(0, 0, 0, 0.25);
}

#playlist .playlist-hero__add-icon {
  width: 18px;
  height: 18px;
}

#playlist .playlist-hero__play-circle {
  width: 33px;
  height: 33px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--color-text-primary); /* 프로젝트 변수 있으면 그대로 활용 */
}

#playlist .playlist-hero__play-circle img {
  width: 100%;
  height: 100%;
}

#playlist .playlist-tracks {
  width: 100%;
  flex: 1 1 auto;
  height: 0;
  min-height: 0;
  overflow-y: auto;
  overscroll-behavior: contain;
  -webkit-overflow-scrolling: touch;
}

#playlist .playlist-tracks::-webkit-scrollbar {
  display: none;
}

#playlist .playlist-tracks__list {
  padding-top: 10px;
  padding-bottom: var(--app-main-bottom);
}

#playlist .playlist-track-item {
  display: flex;
  gap: 10px;
  padding: 10px 5px;
  cursor: pointer;
}

#playlist .playlist-track-item__thumb {
  width: 45px;
  height: 45px;
  flex: 0 0 45px;
  border-radius: 999px;
}

#playlist .playlist-track-item__title {
  font-size: 15px;
  font-weight: bold;
}

#playlist .playlist-track-item__artist {
  color: var(--color-text-secondary);
}

#playlist .playlist-track-item__meta {
  display: flex;
  flex-direction: column;
  justify-content: center;
}
</style>
