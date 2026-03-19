<template>
  <article
    class="playlist-color-card"
    role="button"
    tabindex="0"
    @click="handleSelect"
    @keydown.enter="handleSelect"
    @keydown.space.prevent="handleSelect"
  >
    <div class="playlist-color-bar" :style="{ background: card.color_hex }"></div>

    <div class="playlist-color-content">
      <div class="playlist-color-top">
        <h3 class="playlist-color-name">{{ card.color_name }}</h3>

        <div class="playlist-color-right">
          <span class="playlist-color-arrow">
            <img :src="arrowRight" alt=">" />
          </span>
        </div>
      </div>

      <div class="playlist-song-area">
        <ul class="playlist-song-list">
          <li v-for="(song, index) in card.preview_songs" :key="index">{{ song }}</li>
        </ul>
        <p class="playlist-total">총 {{ card.total_tracks }}곡</p>
      </div>
    </div>
  </article>
</template>

<script setup>
import arrowRight from '@/assets/icons/arrow-right.svg';

const props = defineProps({
  card: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['select']);

function handleSelect() {
  emit('select', props.card);
}
</script>

<style scoped>
.playlist-color-card {
  flex: 0 0 auto;
  width: 100%;
  max-width: 352px;
  height: 120px;
  margin: 0 auto;
  display: flex;
  overflow: hidden;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 0 4px rgba(0, 0, 0, 0.25);
  cursor: pointer;
}

.playlist-color-bar {
  width: 95px;
  height: 120px;
  flex: 0 0 95px;
}

.playlist-color-content {
  flex: 1;
  padding: 10px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.playlist-color-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  padding-bottom: 12px;
}

.playlist-color-name {
  color: #3a586a;
  font-family: 'Pretendard', sans-serif;
  font-size: 26px;
  font-weight: 700;
  line-height: 1;
  margin: 0;
}

.playlist-color-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  padding-top: 10px;
  gap: 10px;
}

.playlist-color-arrow {
  font-size: 20px;
  line-height: 1;
  opacity: 0.55;
}

.playlist-song-area {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 12px;
}

.playlist-song-list {
  flex: 0 0 180px;
  width: 180px;
  margin: 0;
  padding: 0;
  list-style: none;
  font-size: 14px;
  color: #b7aeac;
  line-height: 1.1;
}

.playlist-song-list li {
  margin: 0;
  position: relative;
  padding-left: 10px;
  width: 100%;
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.playlist-song-list li::before {
  content: '•';
  position: absolute;
  left: 0;
  top: 0;
}

.playlist-total {
  flex: 0 0 auto;
  margin: 0;
  font-size: 10px;
  color: #b7aeac;
  white-space: nowrap;
}
</style>
