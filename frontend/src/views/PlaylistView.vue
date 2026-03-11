<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { usePlayerStore } from '@/stores/player';
import { usePaletteLogStore } from '@/stores/paletteLog';
import { useToastStore } from '@/stores/toast';
import PlaylistActionControls from '@/components/PlaylistActionControls.vue';
import trackThumbImage from '@/assets/images/thumb.png';
import likeIcon from '@/assets/icons/like.svg';
import likeFullIcon from '@/assets/icons/like_full.svg';
import { apiRequest } from '@/services/httpClient';
import {
  fetchPlaylistDetail,
  playPlaylistFirstTrack,
  toPlayerPlaylist,
  toPlayerTrack
} from '@/services/playlistService';

const route = useRoute();
const player = usePlayerStore();
const paletteLog = usePaletteLogStore();
const toast = useToastStore();
const playlistId = computed(() => String(route.query.id || '').trim());
const isLoading = ref(false);
const isLikeSubmitting = ref(false);
const errorMessage = ref('');
const playlist = ref(null);
const tracks = ref([]);
const isEnterReady = ref(false);
const isPlaylistSaved = computed(
  () => Boolean(playlist.value?.saved) || paletteLog.has(playlist.value?.id)
);

function formatLikes(value) {
  return Number(value || 0).toLocaleString('en-US');
}

function playTrackAt(trackIndex, openMode = 'main') {
  if (!playlist.value || trackIndex < 0 || trackIndex >= tracks.value.length) return;

  player.setCurrentPlaylist(toPlayerPlaylist(playlist.value));
  player.setQueue(
    tracks.value.map((item) => toPlayerTrack(item, playlist.value)),
    {
      startIndex: trackIndex,
      autoplay: true,
      open_mode: openMode
    }
  );
}

async function loadPlaylistDetail() {
  if (!playlistId.value) {
    playlist.value = null;
    tracks.value = [];
    isEnterReady.value = false;
    player.clearCurrentPlaylist();
    errorMessage.value = '플레이리스트 정보가 없습니다.';
    return;
  }

  isLoading.value = true;
  errorMessage.value = '';
  playlist.value = null;
  tracks.value = [];
  isEnterReady.value = false;

  try {
    await paletteLog.load({ silent: true });

    const result = await fetchPlaylistDetail(playlistId.value);

    playlist.value = result?.playlist ?? null;
    tracks.value = Array.isArray(result?.tracks) ? result.tracks : [];

    if (playlist.value) {
      player.setCurrentPlaylist(toPlayerPlaylist(playlist.value));
      await nextTick();
      requestAnimationFrame(() => {
        isEnterReady.value = true;
      });
    }
  } catch (error) {
    player.clearCurrentPlaylist();
    errorMessage.value =
      error instanceof Error ? error.message : '플레이리스트 정보를 불러오지 못했습니다.';
  } finally {
    isLoading.value = false;
  }
}

async function handleToggleSave() {
  if (!playlist.value?.id) return;

  try {
    const result = await paletteLog.toggle(playlist.value.id);
    if (!result) return;

    playlist.value = {
      ...playlist.value,
      saved: Boolean(result?.saved)
    };

    if (String(player.current_playlist.id || '') === String(playlist.value.id)) {
      player.patchCurrentPlaylist({
        saved: Boolean(result?.saved)
      });
    }

    toast.show(
      Boolean(result?.saved) ? '팔레트로그에 저장되었습니다' : '팔레트로그에서 삭제되었습니다'
    );
  } catch (error) {
    const message =
      error instanceof Error ? error.message : '팔레트 로그 저장 처리에 실패했습니다.';
    window.alert(message);
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
      like_count: nextLikeCount
    });
  } catch (error) {
    const message = error instanceof Error ? error.message : '좋아요 처리에 실패했습니다.';
    window.alert(message);
  } finally {
    isLikeSubmitting.value = false;
  }
}

function handleOpenMainPlayer(track) {
  const trackIndex = tracks.value.findIndex(
    (item) => String(item?.id || '') === String(track?.id || '')
  );
  playTrackAt(trackIndex >= 0 ? trackIndex : 0, 'main');
}

function handleOpenFirstTrack() {
  if (!playlist.value?.id) return;

  playPlaylistFirstTrack(player, playlist.value.id, { autoplay: true, openMode: 'main' }).catch(
    (error) => {
      const message = error instanceof Error ? error.message : '플레이리스트 재생에 실패했습니다.';
      window.alert(message);
    }
  );
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
  <main id="playlist" :class="{ 'is-enter-ready': isEnterReady }">
    <p v-if="isLoading" class="playlist-state">플레이리스트를 불러오는 중...</p>
    <p v-else-if="errorMessage" class="playlist-state playlist-state-error">{{ errorMessage }}</p>

    <section v-else-if="playlist" class="playlist-hero">
      <div
        class="playlist-hero__thumb"
        :style="{ backgroundColor: playlist.color_hex || '#b7aea6' }"
      ></div>
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
          <PlaylistActionControls
            class="playlist-hero__play-actions"
            :saved="isPlaylistSaved"
            :play-disabled="!playlist.id"
            :save-disabled="paletteLog.isPending(playlist.id)"
            @play="handleOpenFirstTrack"
            @save="handleToggleSave"
          />
        </div>
      </div>
    </section>

    <section class="playlist-tracks">
      <ul class="playlist-tracks__list">
        <li
          v-for="(track, trackIndex) in tracks"
          :key="track.id"
          class="playlist-track-item"
          :style="{ '--track-delay': `${Math.min(8, trackIndex) * 36}ms` }"
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
  --playlist-main-side-padding: 25px;
  min-height: 0;
  padding-top: calc(var(--app-header-height) + var(--app-tabs-height));
}

#playlist .playlist-hero,
#playlist .playlist-track-item {
  opacity: 0;
  will-change: transform, opacity;
}

#playlist .playlist-hero {
  transform: translateY(18px) scale(0.985);
}

#playlist .playlist-track-item {
  transform: translateY(12px);
}

#playlist.is-enter-ready .playlist-hero {
  animation: playlist-hero-enter 360ms cubic-bezier(0.2, 0.8, 0.2, 1) both;
}

#playlist.is-enter-ready .playlist-track-item {
  animation: playlist-track-enter 320ms ease both;
  animation-delay: calc(110ms + var(--track-delay, 0ms));
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
  position: sticky;
  top: calc(var(--app-header-height) + var(--app-tabs-height));
  z-index: 1001;
  display: flex;
  flex: 0 0 auto;
  align-items: center;
  gap: 20px;
  margin-inline: calc(var(--playlist-main-side-padding) * -1);
  padding: 0 var(--playlist-main-side-padding) 25px;
  background: var(--color-bg-app);
  box-shadow: 0 10px 12px -12px rgba(0, 0, 0, 0.45);
}

#playlist .playlist-hero__thumb {
  flex: 0 0 59px;
  width: 59px;
  height: 100px;
  border-radius: 30px;
  border: 3px solid #fff;
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
  gap: 5px;
  align-items: center;
  background: transparent;
  border: 0;
  cursor: pointer;
}

#playlist button.playlist-hero__likes:disabled {
  opacity: 0.7;
}

#playlist .playlist-hero__likes span {
  color: var(--color-text-primary);
  font-size: 14px;
}

#playlist .playlist-hero__play-actions {
  margin-left: auto;
}

#playlist .playlist-tracks {
  width: 100%;
  flex: 0 0 auto;
}

#playlist .playlist-tracks__list {
  padding-top: 10px;
  padding-bottom: 0;
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

@keyframes playlist-hero-enter {
  from {
    opacity: 0;
    transform: translateY(18px) scale(0.985);
  }

  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes playlist-track-enter {
  from {
    opacity: 0;
    transform: translateY(12px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
