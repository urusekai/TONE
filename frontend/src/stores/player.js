import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import defaultThumb from '@/assets/images/thumb.png';

function createDefaultPlaylist() {
  return {
    id: '',
    pantoneCode: '',
    colorName: '',
    colorHex: '#B7AEA6',
    liked: false,
    likeCount: 0
  };
}

export const usePlayerStore = defineStore('player', () => {
  const mode = ref('mini'); // 'mini' | 'main' | 'hidden'
  const currentPlaylist = ref(createDefaultPlaylist());
  const currentTrack = ref({
    title: 'Falling Behind',
    artist: 'Laufey',
    cover: defaultThumb
  });
  const isMini = computed(() => mode.value === 'mini');
  const isMain = computed(() => mode.value === 'main');
  const isHidden = computed(() => mode.value === 'hidden');

  function setCurrentPlaylist(playlist) {
    if (!playlist || typeof playlist !== 'object') return;

    currentPlaylist.value = {
      ...createDefaultPlaylist(),
      ...playlist
    };
  }

  function patchCurrentPlaylist(patch) {
    if (!patch || typeof patch !== 'object') return;

    currentPlaylist.value = {
      ...currentPlaylist.value,
      ...patch
    };
  }

  function clearCurrentPlaylist() {
    currentPlaylist.value = createDefaultPlaylist();
  }

  function openMain(track) {
    if (track) currentTrack.value = track;
    mode.value = 'main';
  }

  function openMini(track) {
    if (track) currentTrack.value = track;
    mode.value = 'mini';
  }

  function closeMain() {
    mode.value = 'mini';
  }

  function closeAll() {
    mode.value = 'hidden';
  }

  return {
    mode,
    currentPlaylist,
    currentTrack,
    isMini,
    isMain,
    isHidden,
    setCurrentPlaylist,
    patchCurrentPlaylist,
    clearCurrentPlaylist,
    openMain,
    openMini,
    closeMain,
    closeAll
  };
});
