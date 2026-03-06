<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { usePlayerStore } from '@/stores/player';
import { useAuthStore } from '@/stores/auth';
import { fetchMyProfile } from '@/services/userService';
import { apiRequest } from '@/services/httpClient';

import BottomNav from '@/components/BottomNav.vue';
import TabMenu from '@/components/TabMenu.vue';
import Header from '@/components/Header.vue';
import MiniPlayer from '@/components/MiniPlayer.vue';
import MainPlayer from '@/components/MainPlayer.vue';

const route = useRoute();
const player = usePlayerStore();
const authStore = useAuthStore();
const audioRef = ref(null);

const hasTabs = computed(() => route.meta.hasTabs === true);

function toPlayerPlaylist(value) {
  return {
    id: String(value?.id || ''),
    pantone_code: String(value?.pantone_code || ''),
    color_name: String(value?.color_name || ''),
    color_hex: String(value?.color_hex || '#D5E1E8'),
    liked: Boolean(value?.liked),
    saved: Boolean(value?.saved),
    like_count: Number(value?.like_count || 0)
  };
}

function toPlayerTrack(track, playlist) {
  return {
    id: String(track?.id || ''),
    title: String(track?.title || ''),
    artist: String(track?.artist || ''),
    cover_url: String(track?.cover_url || ''),
    audio_url: String(track?.audio_url || ''),
    video_url: String(track?.video_url || ''),
    duration_ms: Number(track?.duration_ms || 0),
    color_name: String(playlist?.color_name || ''),
    pantone_code: String(playlist?.pantone_code || ''),
    color_hex: String(playlist?.color_hex || '#D5E1E8')
  };
}

async function hydratePlayerWithDailyTone() {
  if (player.has_track) return;

  try {
    const dailyResult = await apiRequest('/api/playlist/daily.php', {}, '오늘의 톤을 불러오지 못했습니다.');
    const dailyPlaylist = dailyResult?.playlist ?? null;

    if (!dailyPlaylist?.id) return;

    const detailResult = await apiRequest(
      `/api/playlist/detail.php?id=${encodeURIComponent(dailyPlaylist.id)}`,
      {},
      '플레이리스트 정보를 불러오지 못했습니다.'
    );

    const playlist = detailResult?.playlist ?? dailyPlaylist;
    const tracks = Array.isArray(detailResult?.tracks) ? detailResult.tracks : [];

    if (tracks.length < 1) return;

    player.setCurrentPlaylist(toPlayerPlaylist(playlist));
    player.setQueue(
      tracks.map((track) => toPlayerTrack(track, playlist)),
      {
        startIndex: 0,
        autoplay: false,
        open_mode: 'mini'
      }
    );
  } catch {
    // 기본 플레이어 초기화 실패는 화면 진입을 막지 않음
  }
}

async function syncPlaybackState() {
  const audio = audioRef.value;
  if (!audio) return;

  if (!player.has_track || !player.current_track.audio_url) {
    audio.pause();
    audio.removeAttribute('src');
    audio.load();
    player.setPlayingState(false);
    player.setCurrentTime(0);
    player.setDuration(0);
    return;
  }

  const nextSource = player.current_track.audio_url.trim();
  if ((audio.getAttribute('src') || '') !== nextSource) {
    audio.src = nextSource;
    audio.load();
  }

  if (player.is_playing) {
    try {
      await audio.play();
    } catch {
      player.setPlayingState(false);
    }
    return;
  }

  audio.pause();
}

onMounted(async () => {
  try {
    await authStore.syncMyProfile(fetchMyProfile);
  } catch {
    // 세션이 없는 경우는 무시
  }

  await hydratePlayerWithDailyTone();
  await syncPlaybackState();
});

watch(
  () => player.current_track.audio_url,
  async () => {
    await syncPlaybackState();
  }
);

watch(
  () => player.is_playing,
  async () => {
    await syncPlaybackState();
  }
);

watch(
  () => player.seek_request_time,
  (nextTime) => {
    const audio = audioRef.value;
    if (!audio || nextTime == null) return;
    audio.currentTime = nextTime;
    player.setCurrentTime(audio.currentTime);
    player.clearSeekRequest();
  }
);

function handleLoadedMetadata() {
  const audio = audioRef.value;
  if (!audio) return;
  player.setDuration(audio.duration);
  console.log('[player-duration-debug]', {
    track_id: player.current_track.id,
    title: player.current_track.title,
    duration_ms: Number(player.current_track.duration_ms || 0),
    expected_seconds: Number(player.current_track.duration_ms || 0) / 1000,
    audio_duration: audio.duration
  });
}

function handleDurationChange() {
  const audio = audioRef.value;
  if (!audio) return;
  player.setDuration(audio.duration);
}

function handleTimeUpdate() {
  const audio = audioRef.value;
  if (!audio) return;
  player.setCurrentTime(audio.currentTime);
}

function handleAudioPlay() {
  player.setPlayingState(true);
}

function handleAudioPause() {
  player.setPlayingState(false);
}

function handleEnded() {
  player.handleTrackEnded();
}

onBeforeUnmount(() => {
  const audio = audioRef.value;
  if (!audio) return;
  audio.pause();
});
</script>

<template>
  <div class="app" :class="{ 'has-tabs': hasTabs, 'has-mini-open': player.isMini, 'has-mini-hidden': player.isHidden }">
    <audio
      ref="audioRef"
      preload="metadata"
      @loadedmetadata="handleLoadedMetadata"
      @durationchange="handleDurationChange"
      @timeupdate="handleTimeUpdate"
      @play="handleAudioPlay"
      @pause="handleAudioPause"
      @ended="handleEnded"
    ></audio>
    <Header />
    <TabMenu v-if="hasTabs" />
    <RouterView />

    <MiniPlayer v-if="player.isMini || player.isHidden" />
    <MainPlayer />
    <BottomNav />
  </div>
</template>
