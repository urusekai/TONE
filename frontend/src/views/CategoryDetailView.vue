<template>
  <!-- 페이지 내용 -->
  <main id="category-detail-page">
    <!-- 무드 헤더 -->
    <section ref="moodHeaderEl" class="mood-header">
      <!-- 기존 HTML의 cg-grad + CSS변수 방식 유지 -->
      <div class="cg-grad" :style="gradStyle"></div>

      <h1 class="mood-title">{{ moodTitle }}</h1>

      <div class="mood-tags">
        <span v-for="t in moodTags" :key="t">#{{ t }}</span>
      </div>
    </section>

    <!-- 컬러 리스트 -->
    <section ref="colorListEl" class="color-list">
      <article
        v-for="card in colorCards"
        :key="card.id"
        class="color-card"
        role="button"
        tabindex="0"
        @click="goPlaylist()"
        @keydown.enter="goPlaylist()"
      >
        <div class="color-bar" :style="{ background: card.barColor }"></div>

        <div class="color-content">
          <div class="color-top">
            <h3 class="color-name">{{ card.title }}</h3>

            <div class="color-right">
              <span class="arrow">
                <img :src="arrowRight" alt=">" />
              </span>
            </div>
          </div>

          <div class="song-area">
            <ul class="song-list">
              <li v-for="(s, i) in card.songs" :key="i">{{ s }}</li>
            </ul>
            <p class="total">총 {{ card.total }}곡</p>
          </div>
        </div>
      </article>
    </section>
  </main>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import arrowRight from '@/assets/icons/arrow-right.svg';

const route = useRoute();
const router = useRouter();

// 지금은 “기존 파일 그대로” Energetic을 기본값으로 보여줌
const mood = computed(() => (route.query.mood || 'energetic').toString().toLowerCase());

// (현재는 energetic만 실제 데이터 채워둠: 기존 html 그대로) :contentReference[oaicite:5]{index=5}
const MOCK_BY_MOOD = {
  energetic: {
    title: 'Energetic',
    tags: ['대담한', '압도적', '맥박수'],
    grad: { '--c1': '#ff5e5b', '--c2': '#ff8e53', '--c3': '#fdc830' },
    cards: [
      {
        id: 'energetic-1',
        barColor: '#bb2649',
        title: 'Viva Magenta',
        songs: ["Måneskin - Beggin'", "화사 - I'm a B", 'Sam Smith - Unholy'],
        total: 10
      },
      {
        id: 'energetic-2',
        barColor: '#003399',
        title: 'Electric Blue',
        songs: [
          'Dua Lipa - Levitating',
          '블랙핑크 - How You Like ThatHow You Like That',
          'David Guetta - Titanium'
        ],
        total: 10
      },
      {
        id: 'energetic-3',
        barColor: '#ff0000',
        title: 'Fiery Red',
        songs: ['ITZY - WANNABE', '(여자)아이들 - 퀸카', '에스파 - Next Level'],
        total: 10
      },
      {
        id: 'energetic-4',
        barColor: '#ffd300',
        title: 'Cyber Yellow',
        songs: ['Travis Scott - SICKO MODE', '21 Savage - Redrum', 'Kendrick Lamar - HUMBLE.'],
        total: 10
      }
    ]
  }
};

// mood 매핑: 없는 mood면 energetic fallback
const moodData = computed(() => MOCK_BY_MOOD[mood.value] ?? MOCK_BY_MOOD.energetic);

const moodTitle = computed(() => moodData.value.title);
const moodTags = computed(() => moodData.value.tags);
const gradStyle = computed(() => moodData.value.grad);
const colorCards = computed(() => moodData.value.cards);

// 기존 JS 동작을 Vue로 이식: 카드 클릭 시 playlist로 이동 :contentReference[oaicite:6]{index=6}
function goPlaylist() {
  router.push('/playlist');
}
</script>

<style scoped>
/* ====== category-detail.css 그대로 이식 (외부로 안 뺀 버전) ====== */

/* ===== 전체 ===== :contentReference[oaicite:8]{index=8} */
#category-detail-page {
  width: 100%;
  min-height: 0;
  overflow: hidden;
  padding-left: 0;
  padding-right: 0;
  padding-top: 0;
  padding-bottom: 0;
}

/* ===== 무드 헤더 ===== :contentReference[oaicite:9]{index=9} */
.mood-header {
  position: relative;
  z-index: 1;
  width: 100%;
  flex: 0 0 auto;
  margin: 0;

  /* 여기서 헤더 높이 다시 더하지 말기 */
  padding: 16px var(--layout-x) 20px;

  text-align: center;
  background: #ffffff;
  box-shadow: 0px 2px 6.5px rgba(0, 0, 0, 0.22);
}

.mood-header .cg-grad {
  position: absolute;
  inset: 0;
  border-radius: inherit;
  background: linear-gradient(135deg, var(--c1) 0%, var(--c2) 50%, var(--c3) 100%);
  opacity: 0.4;
  z-index: 0;
}

.mood-title {
  font-size: 40px;
  font-weight: 700;
  padding: 10px;
  padding-top: 90px;
}

.mood-tags {
  display: flex;
  justify-content: center;
  gap: 10px;
  flex-wrap: wrap;
  font-size: 13px;
  opacity: 0.8;
}

.mood-title,
.mood-tags {
  position: relative;
  z-index: 1;
}

/* ===== 컬러 리스트 ===== :contentReference[oaicite:10]{index=10} */
/* ✅ 아래만 스크롤 */
.color-list {
  flex: 1 1 auto;
  height: 0;
  min-height: 0;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  overscroll-behavior: contain;

  padding: 25px var(--layout-x) calc(var(--app-main-bottom) + 12px);
  display: flex;
  flex-direction: column;
  gap: 22px;
  /* Firefox */
  scrollbar-width: none;

  /* IE, Edge 레거시 */
  -ms-overflow-style: none;
}

.color-list::-webkit-scrollbar {
  display: none;
}

/* 카드 컨테이너 :contentReference[oaicite:11]{index=11} */
.color-card {
  flex: 0 0 auto;
  width: 100%;
  max-width: 352px;
  height: 120px;
  margin: 0 auto;

  display: flex;
  overflow: hidden;
  border-radius: 18px;

  background: #ffffff;
  box-shadow: 0 0px 4px rgba(0, 0, 0, 0.25);
  cursor: pointer;
}

/* 왼쪽 컬러 바 :contentReference[oaicite:12]{index=12} */
.color-bar {
  width: 95px;
  height: 120px;
  flex: 0 0 95px;
}

/* 텍스트 영역 :contentReference[oaicite:13]{index=13} */
.color-content {
  flex: 1;
  padding: 10px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

/* 상단: 타이틀 + 우측 :contentReference[oaicite:14]{index=14} */
.color-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  padding-bottom: 12px;
}

.color-name {
  color: #3a586a;
  font-family: 'Pretendard', sans-serif;
  font-size: 26px;
  font-weight: 700;
  line-height: 1;
  margin: 0;
}

/* 우측 정렬 묶음 :contentReference[oaicite:15]{index=15} */
.color-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  padding-top: 10px;
  gap: 10px;
}

.arrow {
  font-size: 20px;
  line-height: 1;
  opacity: 0.55;
}

.song-area {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 12px;
}

.song-list {
  flex: 0 0 180px;
  width: 180px;
  margin: 0;
  padding: 0;
  list-style: none;
  font-size: 14px;
  color: #b7aeac;
  line-height: 1.1;
}

.song-list li {
  margin: 0;
  position: relative;
  padding-left: 10px;
  width: 100%;
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ✅ 점을 '안쪽'에 직접 그리기 :contentReference[oaicite:16]{index=16} */
.song-list li::before {
  content: '•';
  position: absolute;
  left: 0;
  top: 0;
}

.total {
  flex: 0 0 auto;
  margin: 0;
  font-size: 10px;
  color: #b7aeac;
  white-space: nowrap;
}
</style>
