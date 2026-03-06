<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { usePlayerStore } from '@/stores/player';
import trackThumbImage from '@/assets/images/thumb.png';
import likeIcon from '@/assets/icons/like.svg';
import likeFullIcon from '@/assets/icons/like_full.svg';
import playCircleIcon from '@/assets/icons/play-circle.svg';
import addIcon from '@/assets/icons/add.svg';
import { apiRequest } from '@/services/httpClient';

const route = useRoute();
const player = usePlayerStore();
const playlistId = computed(() => String(route.query.id || '').trim());
const isLoading = ref(false);
const isLikeSubmitting = ref(false);
const errorMessage = ref('');
const playlist = ref(null);
const tracks = ref([]);

function formatLikes(value) {
  return Number(value || 0).toLocaleString('en-US');
}

function toPlayerPlaylist(value) {
  return {
    id: String(value?.id || ''),
    pantoneCode: String(value?.pantone_code || ''),
    colorName: String(value?.color_name || ''),
    colorHex: String(value?.color_hex || '#B7AEA6'),
    liked: Boolean(value?.liked),
    likeCount: Number(value?.like_count || 0)
  };
}

async function loadPlaylistDetail() {
  if (!playlistId.value) {
    playlist.value = null;
    tracks.value = [];
    player.clearCurrentPlaylist();
    errorMessage.value = '플레이리스트 정보가 없습니다.';
    return;
  }

  isLoading.value = true;
  errorMessage.value = '';
  playlist.value = null;
  tracks.value = [];

  try {
    const result = await apiRequest(
      `/api/playlist/detail.php?id=${encodeURIComponent(playlistId.value)}`,
      {},
      '플레이리스트 정보를 불러오지 못했습니다.'
    );

    playlist.value = result?.playlist ?? null;
    tracks.value = Array.isArray(result?.tracks) ? result.tracks : [];

    if (playlist.value) {
      player.setCurrentPlaylist(toPlayerPlaylist(playlist.value));
    }
  } catch (error) {
    player.clearCurrentPlaylist();
    errorMessage.value =
      error instanceof Error ? error.message : '플레이리스트 정보를 불러오지 못했습니다.';
  } finally {
    isLoading.value = false;
  }
}

async function handleToggleLike() {
  if (!playlist.value?.id || isLikeSubmitting.value) return;

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
          playlist_id: playlist.value.id
        })
      },
      '좋아요 처리에 실패했습니다.'
    );

    const nextLiked = Boolean(result?.liked);
    const nextLikeCount = Number(result?.like_count || 0);

    playlist.value = {
      ...playlist.value,
      liked: nextLiked,
      like_count: nextLikeCount
    };

    player.patchCurrentPlaylist({
      liked: nextLiked,
      likeCount: nextLikeCount
    });
  } catch (error) {
    const message = error instanceof Error ? error.message : '좋아요 처리에 실패했습니다.';
    window.alert(message);
  } finally {
    isLikeSubmitting.value = false;
  }
}

function handleOpenMainPlayer(track) {
  player.openMain({
    title: track?.title || '',
    artist: track?.artist || '',
    cover: track?.cover_url || trackThumbImage,
    url: track?.audio_url || ''
  });
}

function handleOpenFirstTrack() {
  if (!tracks.value.length) return;
  handleOpenMainPlayer(tracks.value[0]);
}

watch(
  playlistId,
  async () => {
    await loadPlaylistDetail();
  },
  { immediate: true }
);
</script>

<template>
  <main id="playlist">
    <p v-if="isLoading" class="playlist-state">플레이리스트를 불러오는 중...</p>
    <p v-else-if="errorMessage" class="playlist-state playlist-state-error">{{ errorMessage }}</p>

    <section v-else-if="playlist" class="playlist-hero">
      <div class="playlist-hero__thumb" :style="{ backgroundColor: playlist.color_hex || '#b7aea6' }"></div>
      <div class="playlist-hero__content">
        <div class="playlist-hero__text">
          <p class="playlist-hero__title">{{ playlist.color_name || '' }}</p>
          <p class="playlist-hero__code">{{ playlist.pantone_code || '' }}</p>
        </div>
        <div class="playlist-hero__actions">
          <button
            type="button"
            class="playlist-hero__likes"
            :disabled="isLikeSubmitting"
            @click="handleToggleLike"
          >
            <img :src="playlist.liked ? likeFullIcon : likeIcon" alt="좋아요" />
            <span>{{ formatLikes(playlist.like_count) }}</span>
          </button>
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
          v-for="track in tracks"
          :key="track.id"
          class="playlist-track-item"
          role="button"
          tabindex="0"
          @click="handleOpenMainPlayer(track)"
          @keydown.enter.prevent="handleOpenMainPlayer(track)"
          @keydown.space.prevent="handleOpenMainPlayer(track)"
        >
          <img
            class="playlist-track-item__thumb"
            :src="track.cover_url || trackThumbImage"
            alt="썸네일"
          />
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

.playlist-state {
  padding: 12px 0;
  font-size: 14px;
  color: #3f5f73;
  text-align: center;
}

.playlist-state-error {
  color: #b42318;
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
  align-items: center;
  background: transparent;
  border: 0;
  cursor: pointer;
}

#playlist button.playlist-hero__likes:disabled {
  opacity: 0.7;
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
