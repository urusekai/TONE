import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import defaultThumb from '@/assets/icons/default-cover.svg';

const DEFAULT_PLAYER_COLOR = '#D5E1E8';

function createDefaultPlaylist() {
  return {
    id: '',
    pantone_code: '',
    color_name: '',
    color_hex: DEFAULT_PLAYER_COLOR,
    liked: false,
    saved: false,
    like_count: 0
  };
}

function createDefaultTrack() {
  return {
    id: '',
    title: '',
    artist: '',
    cover_url: defaultThumb,
    audio_url: '',
    video_url: '',
    duration_ms: 0,
    color_name: '',
    pantone_code: '',
    color_hex: DEFAULT_PLAYER_COLOR
  };
}

function normalizePlaylist(playlist) {
  return {
    ...createDefaultPlaylist(),
    ...(playlist && typeof playlist === 'object' ? playlist : {})
  };
}

function normalizeTrack(track) {
  return {
    ...createDefaultTrack(),
    ...(track && typeof track === 'object' ? track : {}),
    cover_url:
      typeof track?.cover_url === 'string' && track.cover_url.trim()
        ? track.cover_url
        : defaultThumb,
    audio_url: typeof track?.audio_url === 'string' ? track.audio_url : '',
    video_url: typeof track?.video_url === 'string' ? track.video_url : '',
    duration_ms: Number(track?.duration_ms || 0)
  };
}

export const usePlayerStore = defineStore('player', () => {
  const mode = ref('mini');
  const current_playlist = ref(createDefaultPlaylist());
  const current_track = ref(createDefaultTrack());
  const track_queue = ref([]);
  const current_index = ref(-1);
  const is_playing = ref(false);
  const current_time = ref(0);
  const duration = ref(0);
  const seek_request_time = ref(null);
  const is_shuffle = ref(false);
  const repeat_mode = ref('off');

  const isMini = computed(() => mode.value === 'mini');
  const isMain = computed(() => mode.value === 'main');
  const isHidden = computed(() => mode.value === 'hidden');
  const progress_percent = computed(() => {
    if (duration.value <= 0) return 0;
    return Math.min(100, Math.max(0, (current_time.value / duration.value) * 100));
  });
  const has_prev = computed(() => current_index.value > 0);
  const has_next = computed(() => current_index.value >= 0 && current_index.value < track_queue.value.length - 1);
  const has_track = computed(
    () =>
      track_queue.value.length > 0 &&
      typeof current_track.value.audio_url === 'string' &&
      current_track.value.audio_url.trim() !== ''
  );
  const has_video = computed(
    () => typeof current_track.value.video_url === 'string' && current_track.value.video_url.trim() !== ''
  );
  const is_repeat = computed(() => repeat_mode.value !== 'off');
  const is_repeat_all = computed(() => repeat_mode.value === 'all');
  const is_repeat_one = computed(() => repeat_mode.value === 'one');

  function setCurrentPlaylist(playlist) {
    current_playlist.value = normalizePlaylist(playlist);
  }

  function patchCurrentPlaylist(patch) {
    if (!patch || typeof patch !== 'object') return;
    current_playlist.value = {
      ...current_playlist.value,
      ...patch
    };
  }

  function clearCurrentPlaylist() {
    current_playlist.value = createDefaultPlaylist();
  }

  function resetPlaybackState() {
    is_playing.value = false;
    current_time.value = 0;
    duration.value = 0;
    seek_request_time.value = null;
  }

  function getRandomNextIndex() {
    if (track_queue.value.length < 2) {
      return current_index.value;
    }

    let nextIndex = current_index.value;
    while (nextIndex === current_index.value) {
      nextIndex = Math.floor(Math.random() * track_queue.value.length);
    }
    return nextIndex;
  }

  function setQueue(tracks, options = {}) {
    track_queue.value = Array.isArray(tracks) ? tracks.map(normalizeTrack) : [];

    if (track_queue.value.length < 1) {
      current_index.value = -1;
      current_track.value = createDefaultTrack();
      resetPlaybackState();
      return;
    }

    const startIndex = Number.isInteger(options.startIndex) ? options.startIndex : 0;
    const safeIndex = Math.min(Math.max(startIndex, 0), track_queue.value.length - 1);

    current_index.value = safeIndex;
    current_track.value = track_queue.value[safeIndex];
    current_time.value = 0;
    duration.value = 0;
    seek_request_time.value = 0;
    is_playing.value = Boolean(options.autoplay);

    if (options.open_mode === 'main') {
      mode.value = 'main';
    } else if (options.open_mode === 'mini') {
      mode.value = 'mini';
    } else if (options.open_mode === 'hidden') {
      mode.value = 'hidden';
    }
  }

  function playTrackAt(index, options = {}) {
    if (index < 0 || index >= track_queue.value.length) return;

    current_index.value = index;
    current_track.value = track_queue.value[index];
    current_time.value = 0;
    duration.value = 0;
    seek_request_time.value = 0;
    is_playing.value = options.autoplay ?? true;

    if (options.open_mode === 'main') {
      mode.value = 'main';
    } else if (options.open_mode === 'mini') {
      mode.value = 'mini';
    } else if (options.open_mode === 'hidden') {
      mode.value = 'hidden';
    }
  }

  function playTrack(track, options = {}) {
    const normalizedTrack = normalizeTrack(track);

    if (Array.isArray(options.queue) && options.queue.length > 0) {
      const normalizedQueue = options.queue.map(normalizeTrack);
      track_queue.value = normalizedQueue;
      const queueIndex =
        Number.isInteger(options.startIndex) && options.startIndex >= 0
          ? options.startIndex
          : normalizedQueue.findIndex((item) => item.id === normalizedTrack.id);

      playTrackAt(queueIndex >= 0 ? queueIndex : 0, {
        autoplay: options.autoplay ?? true,
        open_mode: options.open_mode ?? 'main'
      });
      return;
    }

    track_queue.value = [normalizedTrack];
    playTrackAt(0, {
      autoplay: options.autoplay ?? true,
      open_mode: options.open_mode ?? 'main'
    });
  }

  function togglePlay() {
    if (!has_track.value) return;
    is_playing.value = !is_playing.value;
  }

  function seekToRatio(ratio) {
    if (duration.value <= 0) return;
    const nextRatio = Math.min(Math.max(ratio, 0), 1);
    seek_request_time.value = duration.value * nextRatio;
  }

  function clearSeekRequest() {
    seek_request_time.value = null;
  }

  function replayCurrentTrack() {
    if (!has_track.value || current_index.value < 0) return;
    current_time.value = 0;
    seek_request_time.value = 0;
    is_playing.value = true;
  }

  function playNext() {
    if (track_queue.value.length < 1) return;

    if (is_shuffle.value) {
      playTrackAt(getRandomNextIndex(), { autoplay: true, open_mode: mode.value });
      return;
    }

    if (has_next.value) {
      playTrackAt(current_index.value + 1, { autoplay: true, open_mode: mode.value });
      return;
    }

    if (is_repeat_one.value) {
      replayCurrentTrack();
      return;
    }

    if (is_repeat_all.value) {
      playTrackAt(0, { autoplay: true, open_mode: mode.value });
    }
  }

  function playPrev() {
    if (current_time.value > 3) {
      seek_request_time.value = 0;
      return;
    }

    if (!has_prev.value) return;
    playTrackAt(current_index.value - 1, { autoplay: true, open_mode: mode.value });
  }

  function openMain(track) {
    if (track) {
      current_track.value = normalizeTrack(track);
    }
    mode.value = 'main';
  }

  function openMini(track) {
    if (track) {
      current_track.value = normalizeTrack(track);
    }
    mode.value = 'mini';
  }

  function closeMain() {
    mode.value = 'mini';
  }

  function closeAll() {
    mode.value = 'hidden';
  }

  function setPlayingState(value) {
    is_playing.value = Boolean(value);
  }

  function setCurrentTime(value) {
    current_time.value = Number.isFinite(value) ? Math.max(0, value) : 0;
  }

  function setDuration(value) {
    duration.value = Number.isFinite(value) ? Math.max(0, value) : 0;
  }

  function handleTrackEnded() {
    if (track_queue.value.length < 1) {
      is_playing.value = false;
      current_time.value = duration.value;
      return;
    }

    if (is_repeat_one.value) {
      replayCurrentTrack();
      return;
    }

    if (is_shuffle.value) {
      playTrackAt(getRandomNextIndex(), { autoplay: true, open_mode: mode.value });
      return;
    }

    if (current_index.value < track_queue.value.length - 1) {
      playTrackAt(current_index.value + 1, { autoplay: true, open_mode: mode.value });
      return;
    }

    if (is_repeat_all.value) {
      playTrackAt(0, { autoplay: true, open_mode: mode.value });
      return;
    }

    is_playing.value = false;
    current_time.value = duration.value;
  }

  function toggleShuffle() {
    is_shuffle.value = !is_shuffle.value;
  }

  function toggleRepeat() {
    if (repeat_mode.value === 'off') {
      repeat_mode.value = 'all';
      return;
    }

    if (repeat_mode.value === 'all') {
      repeat_mode.value = 'one';
      return;
    }

    repeat_mode.value = 'off';
  }

  return {
    mode,
    current_playlist,
    current_track,
    track_queue,
    current_index,
    is_playing,
    current_time,
    duration,
    seek_request_time,
    is_shuffle,
    repeat_mode,
    is_repeat,
    is_repeat_all,
    is_repeat_one,
    isMini,
    isMain,
    isHidden,
    progress_percent,
    has_prev,
    has_next,
    has_track,
    has_video,
    setCurrentPlaylist,
    patchCurrentPlaylist,
    clearCurrentPlaylist,
    setQueue,
    playTrack,
    playTrackAt,
    togglePlay,
    seekToRatio,
    clearSeekRequest,
    replayCurrentTrack,
    playNext,
    playPrev,
    toggleShuffle,
    toggleRepeat,
    openMain,
    openMini,
    closeMain,
    closeAll,
    setPlayingState,
    setCurrentTime,
    setDuration,
    handleTrackEnded
  };
});
