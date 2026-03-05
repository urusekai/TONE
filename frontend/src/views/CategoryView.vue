<template>
  <main id="category-page" aria-label="Categories">
    <h1 class="cg-title">Categories</h1>

    <ul class="cg-list">
      <li v-for="item in categories" :key="item.mood" class="cg-item">
        <RouterLink
          class="cg-link"
          :to="{ path: '/category-detail', query: { mood: item.mood } }"
          :aria-label="`${item.label} 카테고리 보기`"
        >
          <div class="cg-grad" :style="item.grad" aria-hidden="true"></div>

          <div class="cg-body">
            <div class="cg-top">
              <p class="cg-word">{{ item.label }}</p>
            </div>

            <div class="cg-tags" aria-label="태그">
              <span v-for="t in item.tags" :key="t" class="cg-tag">#{{ t }}</span>
            </div>

            <div class="cg-arrow" aria-hidden="true">
              <img :src="arrowRight" alt="" />
            </div>
          </div>
        </RouterLink>
      </li>
    </ul>
  </main>
</template>

<script setup>
import arrowRight from '@/assets/icons/arrow-right.svg';

const categories = [
  {
    mood: 'chill',
    label: 'Chill',
    tags: ['Lo-fi', '재즈', '어쿠스틱'],
    grad: { '--c1': '#eaf4f4', '--c2': '#f2f2ee', '--c3': '#dceae8' }
  },
  {
    mood: 'bright',
    label: 'Bright',
    tags: ['청량 K-POP', '썸', '드라이브 팝'],
    grad: { '--c3': '#fff3b0', '--c2': '#a8e6cf', '--c1': '#cde7f0' }
  },
  {
    mood: 'energetic',
    label: 'Energetic',
    tags: ['댄스', 'EDM', '아이돌'],
    grad: { '--c1': '#ff5e5b', '--c2': '#ff8e53', '--c3': '#fdc830' }
  },
  {
    mood: 'emotional',
    label: 'Emotional',
    tags: ['발라드', '슬픈 힙합', 'OST'],
    grad: { '--c1': '#c08497', '--c2': '#8e7dbe', '--c3': '#d6cadd' }
  },
  {
    mood: 'groovy',
    label: 'Groovy',
    tags: ['R&B', '시티팝', '네오소울'],
    grad: { '--c2': '#1f6f78', '--c1': '#3ba7a0', '--c3': '#274c77' }
  },
  {
    mood: 'intense',
    label: 'Intense',
    tags: ['락', 'Drill', '다크 EDM'],
    grad: { '--c3': '#2b2d42', '--c2': '#8d314a', '--c1': '#3a0ca3' }
  }
];
</script>

<style scoped>
/* ==================================================
   Category Page (category.css)
   - common.css 레이아웃 위에 얹는 페이지 전용 스타일
================================================== */

/* main 기본(center) 정렬을 카테고리 페이지에서는 리스트용으로 override */
#category-page {
  width: 100%;
  align-items: stretch;
  justify-content: flex-start;
  gap: 14px;
}

/* 타이틀 */
.cg-title {
  width: 100%;
  font-size: 26px;
  font-weight: 800;
  line-height: 1.1;
  margin-top: 4px;
  margin-bottom: 4px;
}

/* 리스트 */
.cg-list {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

/* 카드 */
.cg-item {
  width: 100%;
}

/* 카드 링크(전체 클릭) */
.cg-link {
  position: relative;
  display: flex;
  align-items: center;
  gap: 14px;

  width: 100%;
  min-height: 92px;

  padding: 18px 18px;
  border-radius: 18px;

  text-decoration: none;
  color: inherit;
  /* 카드 기본 바탕 */
  background: #ffffff;
  box-shadow: 0 0px 15px rgba(0, 0, 0, 0.13);
  border: 1px solid #b7aea671;

  overflow: hidden;
  /* 배경 레이어 깔끔하게 */
  -webkit-tap-highlight-color: transparent;
  transition:
    transform 0.08s ease,
    box-shadow 0.18s ease;
}

.cg-link:active {
  transform: scale(0.99);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
}

/* 카드 전체 배경 그라디언트 레이어 */
.cg-grad {
  position: absolute;
  inset: 0;
  border-radius: inherit;

  background: linear-gradient(
    135deg,
    var(--c1, #f2f2ee) 0%,
    var(--c2, #cfe6d6) 50%,
    var(--c3, #b7aea6) 100%
  );

  /* 핵심: "배경 느낌"만 남기기 */
  opacity: 0.4;
  filter: saturate(0.95);

  z-index: 0;
}

/* 텍스트가 배경 위로 올라오도록 */
.cg-body {
  position: relative;
  z-index: 1;

  flex: 1;
  min-width: 0;
  padding-right: 22px;
  /* 화살표 영역 확보 */
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.cg-arrow {
  position: absolute;
  right: 5px;
  top: 50%;
  transform: translateY(-50%);
  opacity: 0.6;
  pointer-events: none;
}

.cg-arrow img {
  display: block;
  width: 18px;
  height: 18px;
}
/* 상단: 무드 단어 */
.cg-top {
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.cg-word {
  font-size: 22px;
  font-weight: 800;
  letter-spacing: -0.02em;
  line-height: 1.1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* 태그 래핑 */
.cg-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

/* 태그 pill(배경 위에서도 읽히게 살짝 선명하게) */
.cg-tag {
  display: inline-flex;
  align-items: center;
  height: 24px;

  border-radius: 999px;
  color: rgba(63, 95, 115, 0.9);

  font-size: 12px;
  font-weight: 700;
  line-height: 1;
  white-space: nowrap;
}

/* 보조: 카드 전체가 너무 빡빡하면 아래 여유 */
.cg-list {
  padding-bottom: 6px;
}
</style>
