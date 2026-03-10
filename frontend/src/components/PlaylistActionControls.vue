<script setup>
import addIcon from '@/assets/icons/add.svg';
import addCompleteIcon from '@/assets/icons/addComplete.svg';
import playCircleIcon from '@/assets/icons/play-circle.svg';

defineProps({
  saved: {
    type: Boolean,
    default: false
  },
  playDisabled: {
    type: Boolean,
    default: false
  },
  saveDisabled: {
    type: Boolean,
    default: false
  }
});

defineEmits(['play', 'save']);
</script>

<template>
  <div class="playlist-action-controls">
    <button
      type="button"
      class="playlist-action-controls__play"
      :disabled="playDisabled"
      @click="$emit('play')"
    >
      <img :src="playCircleIcon" alt="재생" />
    </button>
    <button
      type="button"
      class="playlist-action-controls__save"
      :disabled="saveDisabled"
      @click="$emit('save')"
    >
      <img :src="saved ? addCompleteIcon : addIcon" alt="저장" />
    </button>
  </div>
</template>

<style scoped>
.playlist-action-controls {
  width: fit-content;
  padding: 4px;
  border-radius: 25px;
  background: #f2f2ee;
  display: inline-flex;
  align-items: center;
  box-shadow: inset 0 0 4px rgba(0, 0, 0, 0.25);
}

.playlist-action-controls__play,
.playlist-action-controls__save {
  border: 0;
  background: transparent;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  cursor: pointer;
}

.playlist-action-controls__play {
  width: 40px;
  height: 40px;
}

.playlist-action-controls__save {
  padding: 0 12px;
}

.playlist-action-controls__play:disabled,
.playlist-action-controls__save:disabled {
  opacity: 0.7;
}

.playlist-action-controls__play img {
  width: 40px;
  height: 40px;
}

.playlist-action-controls__save img {
  width: 14px;
  height: 14px;
}
</style>
