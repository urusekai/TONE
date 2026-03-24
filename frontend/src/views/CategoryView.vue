<template>
  <main id="category-page" :class="{ 'is-enter-ready': isEnterReady }" aria-label="Categories">
    <h1 class="cg-title">Categories</h1>

    <p v-if="isLoading" class="cg-state">카테고리를 불러오는 중...</p>
    <p v-else-if="errorMessage" class="cg-state cg-state-error">{{ errorMessage }}</p>

    <ul v-else class="cg-list">
      <li
        v-for="(item, index) in categories"
        :key="item.mood"
        class="cg-item"
        :style="{ '--card-delay': `${Math.min(10, index) * 38}ms` }"
      >
        <RouterLink
          class="cg-link"
          :to="{
            path: '/category-detail',
            query: {
              mood: item.mood,
              label: item.label,
              tag1: item.tags[0] || '',
              tag2: item.tags[1] || '',
              tag3: item.tags[2] || '',
              gradC1: item.grad.c1,
              gradC2: item.grad.c2,
              gradC3: item.grad.c3
            }
          }"
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
import { nextTick, onMounted, ref } from 'vue';
import arrowRight from '@/assets/icons/arrow-right.svg';
import { apiRequest } from '@/services/httpClient';

const categories = ref([]);
const isLoading = ref(false);
const errorMessage = ref('');
const isEnterReady = ref(false);

async function loadCategories() {
  isLoading.value = true;
  errorMessage.value = '';
  isEnterReady.value = false;

  try {
    const result = await apiRequest(
      '/api/categories/list.php',
      {},
      '카테고리 목록을 불러오지 못했습니다.'
    );
    const items = Array.isArray(result?.categories) ? result.categories : [];

    categories.value = items.map((item) => ({
      mood: String(item.mood || '').toLowerCase(),
      label: String(item.label || ''),
      tags: [item.tag1, item.tag2, item.tag3].map((tag) => String(tag || '')),
      grad: {
        '--c1': String(item.grad_c1 || '#f2f2ee'),
        '--c2': String(item.grad_c2 || '#cfe6d6'),
        '--c3': String(item.grad_c3 || '#b7aea6'),
        c1: String(item.grad_c1 || '#f2f2ee'),
        c2: String(item.grad_c2 || '#cfe6d6'),
        c3: String(item.grad_c3 || '#b7aea6')
      }
    }));
    await nextTick();
    requestAnimationFrame(() => {
      isEnterReady.value = true;
    });
  } catch (error) {
    categories.value = [];
    errorMessage.value =
      error instanceof Error ? error.message : '카테고리 목록을 불러오지 못했습니다.';
  } finally {
    isLoading.value = false;
  }
}

onMounted(async () => {
  await loadCategories();
});
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

#category-page .cg-title,
#category-page .cg-item {
  opacity: 0;
  transform: translateY(12px);
  will-change: transform, opacity;
}

#category-page.is-enter-ready .cg-title {
  animation: category-page-title-enter 320ms cubic-bezier(0.2, 0.8, 0.2, 1) both;
}

#category-page.is-enter-ready .cg-item {
  animation: category-page-card-enter 320ms ease both;
  animation-delay: var(--card-delay, 0ms);
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

.cg-state {
  width: 100%;
  padding: 14px 4px;
  font-size: 14px;
  color: #3f5f73;
}

.cg-state-error {
  color: #b42318;
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
  box-shadow: 0 0px 10px rgba(0, 0, 0, 0.16);
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

@keyframes category-page-title-enter {
  from {
    opacity: 0;
    transform: translateY(12px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes category-page-card-enter {
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
