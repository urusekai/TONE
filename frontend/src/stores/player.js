import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import defaultThumb from '@/assets/images/thumb.png';

export const usePlayerStore = defineStore('player', () => {
  const mode = ref('mini'); // 'mini' | 'main' | 'hidden'
  const currentTrack = ref({
    title: 'Falling Behind',
    artist: 'Laufey',
    cover: defaultThumb
  });
  const isMini = computed(() => mode.value === 'mini');
  const isMain = computed(() => mode.value === 'main');
  const isHidden = computed(() => mode.value === 'hidden');

  function openMain(track) {
    if (track) currentTrack.value = track;
    mode.value = 'main';
  }

  function closeMain() {
    mode.value = 'mini';
  }

  function closeAll() {
    mode.value = 'hidden';
  }

  return {
    mode,
    currentTrack,
    isMini,
    isMain,
    isHidden,
    openMain,
    closeMain,
    closeAll
  };
});
