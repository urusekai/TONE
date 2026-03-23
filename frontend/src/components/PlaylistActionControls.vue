<script setup>
import { computed } from 'vue';
import addIcon from '@/assets/icons/add.svg';
import addCompleteIcon from '@/assets/icons/addComplete.svg';
import playIcon from '@/assets/icons/play.svg';

const props = defineProps({
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
  },
  playColor: {
    type: String,
    default: ''
  },
  surface: {
    type: String,
    default: 'default'
  }
});

defineEmits(['play', 'save']);

const rootClassName = computed(() => [
  'playlist-action-controls',
  props.surface === 'white' ? 'playlist-action-controls--white' : ''
]);

const playButtonStyle = computed(() =>
  props.playColor
    ? {
        '--playlist-action-play-fill': props.playColor
      }
    : null
);
</script>

<template>
  <div :class="rootClassName">
    <button
      type="button"
      class="playlist-action-controls__play"
      :style="playButtonStyle"
      :disabled="playDisabled"
      @click="$emit('play')"
    >
      <svg
        class="playlist-action-controls__play-icon"
        width="40"
        height="40"
        viewBox="0 0 50 50"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
      >
        <path
          d="M50 25C50 11.1929 38.8071 0 25 0C11.1929 0 0 11.1929 0 25C0 38.8071 11.1929 50 25 50C38.8071 50 50 38.8071 50 25Z"
          fill="var(--playlist-action-play-fill, #3F5F73)"
        />
        <path
          d="M31.448 21.6656L22.6647 16.4329C22.1894 16.1479 21.6499 15.9985 21.1011 16C20.5523 16.0015 20.0136 16.154 19.5399 16.4418C19.0661 16.7296 18.6741 17.1425 18.4036 17.6387C18.1332 18.1348 17.994 18.6964 18.0002 19.2665V29.7697C18.0002 30.6265 18.3278 31.4481 18.911 32.0538C19.4941 32.6597 20.2851 33 21.1098 33C21.6558 32.999 22.1919 32.8492 22.6647 32.5656L31.448 27.3329C31.92 27.049 32.3119 26.6413 32.5842 26.1506C32.8566 25.66 33 25.1035 33 24.537C33 23.9705 32.8566 23.4141 32.5842 22.9233C32.3119 22.4326 31.92 22.0249 31.448 21.7412V21.6656ZM30.5388 25.6232L21.7554 30.9315C21.5585 31.0474 21.336 31.1083 21.1098 31.1083C20.8835 31.1083 20.6611 31.0474 20.4642 30.9315C20.268 30.8137 20.1049 30.6444 19.9916 30.4405C19.8782 30.2365 19.8186 30.0052 19.8186 29.7697V19.2287C19.8186 18.9932 19.8782 18.7619 19.9916 18.558C20.1049 18.354 20.268 18.1847 20.4642 18.067C20.6619 17.9528 20.8836 17.8912 21.1098 17.8874C21.3358 17.8923 21.5573 17.9539 21.7554 18.067L30.5388 23.3374C30.7352 23.4551 30.8983 23.6245 31.0117 23.8283C31.125 24.0323 31.1847 24.2637 31.1847 24.4992C31.1847 24.7347 31.125 24.9661 31.0117 25.17C30.8983 25.3739 30.7352 25.5433 30.5388 25.6609V25.6232Z"
          fill="#F1F1ED"
        />
      </svg>
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

.playlist-action-controls--white {
  background: #ffffff;
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

.playlist-action-controls__play-icon {
  width: 40px;
  height: 40px;
  filter: drop-shadow(0 0 4px rgba(0, 0, 0, 0.18));
}

.playlist-action-controls__save img {
  width: 14px;
  height: 14px;
}
</style>
