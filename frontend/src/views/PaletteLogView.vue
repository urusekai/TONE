<template>
  <main id="palette-log-page" :class="{ 'is-anim': isAnim }">
    <section class="pl">
      <div class="pl-head">
        <h1 class="pl-title">Palette Log</h1>
        <p class="pl-sub">Current Playlist</p>
      </div>

      <div class="pl-board">
        <ul class="pl-stack" :class="{ 'is-lock': isLock }">
          <li
            v-for="(item, i) in items"
            :key="item.id"
            class="pl-card"
            :class="{ 'is-in': isAnim, 'is-exit-right': exitId === item.id }"
            :style="{
              '--card': item.color,
              '--delay': `${i * 50}ms`
            }"
          >
            <!-- ✅ 디자인은 a처럼, 동작은 우리가 제어 -->
            <button type="button" class="pl-link" @click="onCardClick(item)">
              <span class="pl-arrow">
                <img :src="arrowIcon" alt=">" />
              </span>

              <h2 class="pl-name">{{ item.name }}</h2>
              <div class="pl-line"></div>

              <div class="pl-meta">
                <span>
                  <img :src="likeIcon" alt="like_full" />
                  {{ item.likes }}
                </span>
                <span>
                  <img :src="noteIcon" alt="note" />
                  {{ item.plays }}
                </span>
              </div>
            </button>
          </li>
        </ul>
      </div>
    </section>
  </main>
</template>

<script setup>
import { onMounted, onBeforeUnmount, nextTick, ref } from 'vue';
import { useRouter } from 'vue-router';

import arrowIcon from '@/assets/icons/arrow-right.svg';
import likeIcon from '@/assets/icons/like_full.svg';
import noteIcon from '@/assets/icons/music_note.svg';

const router = useRouter();

const isAnim = ref(false);
const isLock = ref(false);
const exitId = ref(null);
const isTransitioning = ref(false);

// ✅ 더미 데이터 (너가 만든 그대로)
const items = ref([
  {
    id: '02.23|Baby Blue',
    name: 'Baby Blue',
    color: '#b6c6d3',
    likes: '0,000',
    plays: '100 Plays'
  },
  {
    id: '02.22|Lavender',
    name: 'Lavender',
    color: '#aaa4ca',
    likes: '0,000',
    plays: '100 Plays'
  },
  {
    id: '02.21|Prism Pink',
    name: 'Prism Pink',
    color: '#e8a4bb',
    likes: '0,000',
    plays: '100 Plays'
  },
  {
    id: '02.20|Living Coral',
    name: 'Living Coral',
    color: '#fa7268',
    likes: '0,000',
    plays: '100 Plays'
  },
  {
    id: '02.19|Serenity',
    name: 'Serenity',
    color: '#91a8d0',
    likes: '0,000',
    plays: '100 Plays'
  },
  {
    id: '02.18|Viva Magenta',
    name: 'Viva Magenta',
    color: '#bb2649',
    likes: '0,000',
    plays: '100 Plays'
  },
  {
    id: '02.17|Gray Lilac',
    name: 'Gray Lilac',
    color: '#d4cacd',
    likes: '0,000',
    plays: '100 Plays'
  },
  {
    id: '02.16|Mountain Trail',
    name: 'Mountain Trail',
    color: '#8a756a',
    likes: '0,000',
    plays: '100 Plays'
  },
  {
    id: '02.15|Arona',
    name: 'Arona',
    color: '#899aa2',
    likes: '0,000',
    plays: '100 Plays'
  },
  {
    id: '02.14|Regatta',
    name: 'Regatta',
    color: '#497ab7',
    likes: '0,000',
    plays: '100 Plays'
  }
]);

/**
 * ✅ 템플릿에서 쓰는 카드 클릭 핸들러(원하면 유지)
 * - 이 함수는 "수동 클릭"에서 쓰고,
 * - DOM 이벤트 위임 방식(onClick)과 동시 사용해도 되는데,
 *   중복 클릭 방지를 위해 결국 isTransitioning으로 한번만 타게 됨.
 */
function onCardClick(item) {
  if (isTransitioning.value) return;
  isTransitioning.value = true;

  isLock.value = true;
  exitId.value = item.id;

  localStorage.setItem('tone.player.payload', JSON.stringify(item));

  // ⚠️ setTimeout 말고 transitionend로 이동하는 게 더 정확함
  // 하지만 템플릿이 @click="onCardClick(item)"만 쓰는 경우를 대비해서 fallback로 유지
  window.setTimeout(() => {
    router.push('/playlist');
  }, 350);
}

/* =========================
   아래부터: "palette-log.js stable"를 Vue용으로 이식 (최우선)
========================= */

let mo = null;
let onResize = null;
let onClick = null;

onMounted(async () => {
  await nextTick(); // ✅ v-for 렌더 후 DOM 확보

  const page = document.getElementById('palette-log-page');
  const stack = document.querySelector('.pl-stack');
  if (!page || !stack) return;

  const getCards = () => Array.from(stack.querySelectorAll('.pl-card'));

  // 1) 순차 등장 (CSS: #palette-log-page.is-anim .pl-card ...)
  page.classList.add('is-anim');
  getCards().forEach((card, i) => {
    card.style.setProperty('--delay', `${i * 50}ms`);
    requestAnimationFrame(() => card.classList.add('is-in'));
  });

  // Vue ref도 같이 맞춰둠(템플릿이 :class로 isAnim 쓰는 경우 대비)
  requestAnimationFrame(() => {
    isAnim.value = true;
  });

  // 2) z-index 자동 부여
  function applyStackZIndex() {
    const cards = getCards();
    const total = cards.length;
    cards.forEach((card, i) => {
      card.style.zIndex = String(total - i);
    });
  }
  applyStackZIndex();

  // 3) 보드 높이 자동 계산
  function updateBoardHeight() {
    const cards = getCards();
    if (!cards.length) return;

    const overlap = 50; // 기존 stable 값 유지
    const cardHeight = cards[0].offsetHeight;
    const total = cards.length;
    const totalHeight = cardHeight + (total - 1) * (cardHeight - overlap);

    stack.style.minHeight = totalHeight + 'px';
  }
  updateBoardHeight();

  onResize = () => updateBoardHeight();
  window.addEventListener('resize', onResize);

  // 4) 카드 추가/삭제 감지 → z-index/높이 갱신
  mo = new MutationObserver(() => {
    applyStackZIndex();
    updateBoardHeight();

    getCards().forEach((card) => {
      if (!card.classList.contains('is-in')) card.classList.add('is-in');
    });
  });
  mo.observe(stack, { childList: true });

  // 5) 클릭한 카드 1장만 오른쪽 슬라이드 (이게 진짜 “정확한” 이동)
  onClick = (e) => {
    const link = e.target.closest('.pl-link');
    if (!link) return;

    e.preventDefault();
    if (isTransitioning.value) return;

    const card = link.closest('.pl-card');
    if (!card) return;

    isTransitioning.value = true;
    isLock.value = true;

    // exitId는 Vue 템플릿에서 class 바인딩으로도 쓸 수 있게 맞춤
    // (템플릿에서 :class="{ 'is-exit-right': exitId===item.id }"라면)
    const date = card.querySelector('.pl-time')?.textContent?.trim() || '';
    const name = card.querySelector('.pl-name')?.textContent?.trim() || '';
    const color = getComputedStyle(card).getPropertyValue('--card')?.trim() || '';

    const id = `${date}|${name}|${color}`;
    exitId.value = id;

    // ✅ class 직접 부여(템플릿 바인딩이 없어도 동작)
    stack.classList.add('is-lock');
    card.classList.add('is-exit-right');

    const payload = { id, date, name, color };
    localStorage.setItem('tone.player.payload', JSON.stringify(payload));

    const onEnd = (ev) => {
      if (ev.propertyName !== 'transform') return;
      card.removeEventListener('transitionend', onEnd);
      router.push('/playlist');
    };

    card.addEventListener('transitionend', onEnd);
  };

  stack.addEventListener('click', onClick);
});

onBeforeUnmount(() => {
  if (onResize) window.removeEventListener('resize', onResize);
  if (mo) mo.disconnect();

  const stack = document.querySelector('.pl-stack');
  if (stack && onClick) stack.removeEventListener('click', onClick);
});
</script>
<style scoped>
/* =========================
   Palette Log (reset)
   ========================= */
* {
  box-sizing: border-box;
}

/* main 영역: 부모 flex 센터링 영향 차단 */
#palette-log-page {
  display: block;
  width: 100%;
  margin: 0;
  align-self: stretch;
  position: relative;
  z-index: 2;
  will-change: transform;
  transition: transform 0.45s cubic-bezier(0.2, 0.8, 0.2, 1);
}

/* 헤더 텍스트 */
.pl-head {
  margin: 8px 0 70px;
}

.pl-title {
  font-size: 25px;
  font-weight: 800;
  letter-spacing: -0.02em;
  line-height: 1.15;
}

.pl-sub {
  margin-top: 4px;
  font-size: 14px;
  font-weight: 800;
  opacity: 0.75;
}

/* =========================
   Board
   ========================= */
.pl-board {
  width: 100%;
  height: auto;
  margin: 0 auto;
  position: relative;
  padding-top: 22px;
  overflow: visible;
  background: #e1d8d6;
  box-shadow:
    0px 2px 4px 2px rgba(0, 0, 0, 0.2),
    0px 4px 4px 0px rgba(0, 0, 0, 0.25);
}

/* =========================
   Stack + Cards
   ========================= */
.pl-stack {
  position: relative;
  z-index: 1;
  list-style: none;
  padding-top: 0;
  padding-bottom: 70px;
  margin: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.pl-card {
  width: 320px;
  height: 134px;
  background: var(--card, #bfc9d6);
  overflow: hidden;
  margin-top: -28px;
  box-shadow:
    0 16px 26px rgba(0, 0, 0, 0.18),
    0 1px 0 rgba(255, 255, 255, 0.25) inset;
  opacity: 1;
  transform: none;
  will-change: transform, opacity;
}

.pl-card:first-child {
  margin-top: -70px;
}

/* 링크 내부 */
.pl-link {
  display: block;
  width: 100%;
  height: 100%;
  position: relative;
  padding: 14px 14px 12px;
  text-decoration: none;
  color: #fff;

  /* button reset */
  border: 0;
  background: transparent;
  text-align: left;
  cursor: pointer;
}

/* 화살표 */
.pl-arrow {
  position: absolute;
  right: 12px;
  top: 45px;
  opacity: 0.9;
}

.pl-arrow img {
  display: block;
  width: 6px;
  height: 10px;
  filter: brightness(0) invert(1);
}

.pl-card:first-child .pl-arrow {
  top: 10px;
}

/* 제목 */
.pl-name {
  margin: 10px 0 8px;
  padding-top: 10px;
  padding-left: 5px;
  padding-bottom: 5px;
  font-size: 30px;
  font-weight: 700;
  letter-spacing: -0.02em;
  text-align: left;
}

/* 구분선 */
.pl-line {
  height: 1px;
  background: rgba(255, 255, 255, 0.32);
  margin: 8px 2px 10px;
}

/* 메타 */
.pl-meta {
  display: flex;
  padding-top: 5px;
  gap: 14px;
  align-items: center;
  font-size: 13px;
  font-weight: 700;
  color: #fff;
  opacity: 1;
}

.pl-meta span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.pl-meta img {
  width: 12px;
  height: 12px;
  opacity: 1;
  filter: brightness(0) invert(1);
}

/* =========================
   A) 카드 순차 등장
========================= */
#palette-log-page.is-anim .pl-card {
  opacity: 0;
  transform: translateY(10px);
  transition:
    opacity 0.36s ease,
    transform 0.36s ease;
  transition-delay: var(--delay, 0ms);
}

#palette-log-page.is-anim .pl-card.is-in {
  opacity: 1;
  transform: translateY(0);
}

/* =========================
   B) 오른쪽으로 빠지는 전환
========================= */
#palette-log-page.is-anim .pl-card.is-exit-right {
  transform: translateX(140%);
  opacity: 0;
  transition:
    transform 1.5s cubic-bezier(0.2, 0.8, 0.2, 1),
    opacity 0.25s ease;
}

/* 클릭 순간 다른 카드 잠금 */
.pl-stack.is-lock .pl-card:not(.is-exit-right) {
  pointer-events: none;
}
</style>
