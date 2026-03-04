<template>
  <!-- 페이지 내용 -->
  <main ref="rootEl" id="main-page" class="home">
    <!-- 1) Daily tone -->
    <section class="panel daily">
      <div class="daily-pill">Daily tone</div>

      <div class="daily-inner">
        <div class="daily-text">
          <h2>데일리 팬톤 컬러 명</h2>
          <p>
            오늘의 톤은 <b>Very Peri</b> 입니다.<br />
            오늘의 톤에 맞는 플레이리스트입니다.
          </p>

          <div class="daily-actions">
            <button
              class="icon-btn big"
              type="button"
              aria-label="play"
              @click="openMainPlayerDaily"
            >
              <img src="@/assets/icons/play.svg" alt="play" />
            </button>

            <button class="icon-btn daily-btn" type="button" aria-label="save" @click="goCalendar">
              <img src="@/assets/icons/calendarSave.svg" alt="calendar" />
            </button>
          </div>
        </div>

        <div class="daily-swatch" aria-hidden="true"></div>
      </div>
    </section>

    <!-- 2) Daily Spectrum -->
    <section class="panel spectrum">
      <div class="panel-head">
        <h3>Daily Spectrum</h3>
        <span class="hint">오늘 톤과 비슷한 색상 추천</span>
      </div>

      <div class="spec-track">
        <div ref="specRowEl" class="spec-row">
          <RouterLink class="spec-card" :to="playlistTo('bordeaux')">
            <div class="spec-color" style="--c: #97637c"></div>
            <div class="spec-body">
              <div class="spec-meta">
                <span class="spec-code">17-1710</span>
                <button
                  class="mini-add"
                  type="button"
                  aria-label="add"
                  @click.capture.stop.prevent="goCalendar"
                >
                  <img src="@/assets/icons/calendarSave.svg" alt="calendar" />
                </button>
              </div>
              <div class="spec-name">Bordeaux</div>
            </div>
          </RouterLink>

          <RouterLink
            class="spec-card is-next"
            :to="playlistTo('scarlet-smile')"
            aria-hidden="true"
          >
            <div class="spec-color" style="--c: #9f2336"></div>
            <div class="spec-body">
              <div class="spec-meta">
                <span class="spec-code">19-1558</span>
                <button
                  class="mini-add"
                  type="button"
                  aria-label="add"
                  @click.capture.stop.prevent="goCalendar"
                >
                  <img src="@/assets/icons/calendarSave.svg" alt="calendar" />
                </button>
              </div>
              <div class="spec-name">Scarlet Smile</div>
            </div>
          </RouterLink>

          <RouterLink
            class="spec-card is-next"
            :to="playlistTo('pink-lemonade')"
            aria-hidden="true"
          >
            <div class="spec-color" style="--c: #ef6f8e"></div>
            <div class="spec-body">
              <div class="spec-meta">
                <span class="spec-code">16-1735</span>
                <button
                  class="mini-add"
                  type="button"
                  aria-label="add"
                  @click.capture.stop.prevent="goCalendar"
                >
                  <img src="@/assets/icons/calendarSave.svg" alt="calendar" />
                </button>
              </div>
              <div class="spec-name">Pink Lemonade</div>
            </div>
          </RouterLink>
        </div>
      </div>
    </section>

    <!-- 3) Palette Log -->
    <section class="panel log">
      <div class="panel-head">
        <h3>Palette Log</h3>

        <!-- ✅ 더보기도 RouterLink로 -->
        <RouterLink class="more" to="/palette-log">더보기</RouterLink>
      </div>

      <div class="log-list">
        <RouterLink class="log-item" :to="playlistTo('veiled-vista')" style="--bg: #cfe6d6">
          <div class="log-top">13-6008</div>
          <div class="log-main">
            <strong>Veiled Vista</strong>
            <span class="chev icon-white">
              <img src="@/assets/icons/arrow-right.svg" alt=">" />
            </span>
          </div>
          <div class="log-sub">♫ 12 Plays</div>
        </RouterLink>

        <RouterLink class="log-item" :to="playlistTo('baltic-sea')" style="--bg: #6faed9">
          <div class="log-top">16-4120</div>
          <div class="log-main">
            <strong>Balric Sea</strong>
            <span class="chev icon-white">
              <img src="@/assets/icons/arrow-right.svg" alt=">" />
            </span>
          </div>
          <div class="log-sub">♫ 12 Plays</div>
        </RouterLink>

        <RouterLink class="log-item" :to="playlistTo('golden-mist')" style="--bg: #e3d9a3">
          <div class="log-top">13-0917</div>
          <div class="log-main">
            <strong>Golden Mist</strong>
            <span class="chev icon-white">
              <img src="@/assets/icons/arrow-right.svg" alt=">" />
            </span>
          </div>
          <div class="log-sub">♫ 12 Plays</div>
        </RouterLink>

        <RouterLink class="log-item" :to="playlistTo('quiet-violet')" style="--bg: #9b8fb3">
          <div class="log-top">17-3725</div>
          <div class="log-main">
            <strong>Quiet Violet</strong>
            <span class="chev icon-white">
              <img src="@/assets/icons/arrow-right.svg" alt=">" />
            </span>
          </div>
          <div class="log-sub">♫ 12 Plays</div>
        </RouterLink>
      </div>
    </section>

    <!-- 4) Echo Notes -->
    <section class="panel echo">
      <div class="panel-head">
        <h3><span class="dot"></span>Echo Notes</h3>
        <span class="hint">다른 사용자들의 한마디</span>
      </div>

      <div class="echo-card">
        <p class="echo-text">오늘은 이 톤이 잘 맞는다</p>
        <div class="echo-line"></div>
        <p class="echo-date">02.26</p>
      </div>
    </section>
  </main>
</template>

<script setup>
import { onMounted, onBeforeUnmount, nextTick, ref } from 'vue';
import { useRouter } from 'vue-router';
import { usePlayerStore } from '@/stores/player';

const router = useRouter();
const player = usePlayerStore();

const rootEl = ref(null);
const specRowEl = ref(null);

let cleanupSpectrumDrag = null;

/* ---------- 라우팅 헬퍼 ---------- */
// 지금은 임시로 /playlist?pid=... 형태
// 라우터를 /playlist/:id 로 쓰면 return { name:'playlist', params:{ id } } 로 바꾸면 됨.
function playlistTo(pid) {
  return { path: '/playlist', query: { pid } };
}

function openMainPlayerDaily() {
  // 1) 플레이어에 트랙/플레이리스트 세팅 (store에 맞춰서)
  player.openMain({
    title: 'Falling Behind',
    artist: 'Laufey',
    cover: new URL('@/assets/images/thumb.png', import.meta.url).href
  });
  // 2) 메인플레이어 열기
  player.openMain();
}

/* ---------- (임시) 저장 버튼 동작 ---------- */
function goCalendar() {
  router.push('/calendar');
}
function onSaveSpectrum(pid) {
  // TODO: 캘린더 저장 로직 연결
  console.log('[save] spectrum:', pid);
}

/* ---------- color utils (hex/rgb/rgba 전부 처리) ---------- */
function parseToRGB(color) {
  if (!color) return null;
  const c = color.trim();

  if (c.startsWith('#')) {
    const hex = c.slice(1);
    if (hex.length !== 6) return null;
    const r = parseInt(hex.slice(0, 2), 16);
    const g = parseInt(hex.slice(2, 4), 16);
    const b = parseInt(hex.slice(4, 6), 16);
    return { r, g, b };
  }

  const m = c.match(/rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)(?:\s*,\s*[\d.]+)?\s*\)/i);
  if (m) return { r: Number(m[1]), g: Number(m[2]), b: Number(m[3]) };

  return null;
}

function getBrightness(color) {
  const rgb = parseToRGB(color);
  if (!rgb) return 0;
  const { r, g, b } = rgb;
  return (r * 299 + g * 587 + b * 114) / 1000;
}

/* ---------- Palette Log: 배경명도에 따라 글자색 자동 ---------- */
function applyLogItemTheme(root) {
  if (!root) return;

  const items = root.querySelectorAll('.log-item');
  items.forEach((item) => {
    let bg = item.style.getPropertyValue('--bg')?.trim();
    if (!bg) bg = getComputedStyle(item).getPropertyValue('--bg').trim();
    if (!bg) return;

    const brightness = getBrightness(bg);

    // ✅ 덮어쓰기 충돌 줄이려면 "class만" 쓰는 게 더 깔끔하지만,
    // 지금은 즉시 눈에 보이게 inline color도 같이 적용
    if (brightness > 170) {
      item.style.color = '#6B6E6E';
      item.classList.add('is-light');
      item.classList.remove('is-dark');
    } else {
      item.style.color = '#F2F2EE';
      item.classList.add('is-dark');
      item.classList.remove('is-light');
    }
  });
}

/* ---------- Spectrum: 드래그 스크롤 ---------- */
function bindSpectrumDrag(row) {
  if (!row) return () => {};

  let isDown = false;
  let startX = 0;
  let startScrollLeft = 0;
  let pointerId = null;

  row.style.cursor = 'grab';
  row.style.userSelect = 'none';

  const onPointerDown = (e) => {
    if (e.pointerType === 'mouse' && e.button !== 0) return;

    isDown = true;
    pointerId = e.pointerId;
    row.setPointerCapture(pointerId);

    startX = e.clientX;
    startScrollLeft = row.scrollLeft;

    row.style.cursor = 'grabbing';
    row.classList.add('is-dragging');
  };

  const onPointerMove = (e) => {
    if (!isDown) return;
    const dx = e.clientX - startX;
    row.scrollLeft = startScrollLeft - dx;
  };

  const endDrag = () => {
    if (!isDown) return;
    isDown = false;
    pointerId = null;
    row.style.cursor = 'grab';
    row.classList.remove('is-dragging');
  };

  // 드래그 중 링크 클릭 방지
  let moved = 0;
  const resetMoved = () => (moved = 0);
  const trackMoved = (e) => {
    if (!isDown) return;
    moved += Math.abs(e.movementX || 0);
  };
  const stopClickWhenDragged = (e) => {
    if (moved > 6) {
      e.preventDefault();
      e.stopPropagation();
    }
  };

  row.addEventListener('pointerdown', onPointerDown);
  row.addEventListener('pointermove', onPointerMove);
  row.addEventListener('pointerup', endDrag);
  row.addEventListener('pointercancel', endDrag);
  row.addEventListener('pointerleave', endDrag);

  row.addEventListener('pointerdown', resetMoved);
  row.addEventListener('pointermove', trackMoved);
  row.addEventListener('click', stopClickWhenDragged, true);

  return () => {
    row.removeEventListener('pointerdown', onPointerDown);
    row.removeEventListener('pointermove', onPointerMove);
    row.removeEventListener('pointerup', endDrag);
    row.removeEventListener('pointercancel', endDrag);
    row.removeEventListener('pointerleave', endDrag);

    row.removeEventListener('pointerdown', resetMoved);
    row.removeEventListener('pointermove', trackMoved);
    row.removeEventListener('click', stopClickWhenDragged, true);
  };
}

onMounted(async () => {
  await nextTick();

  cleanupSpectrumDrag = bindSpectrumDrag(specRowEl.value);

  // 스타일 적용 타이밍 보장(2프레임)
  requestAnimationFrame(() => {
    requestAnimationFrame(() => applyLogItemTheme(rootEl.value));
  });
});

onBeforeUnmount(() => {
  if (cleanupSpectrumDrag) cleanupSpectrumDrag();
});
</script>

<style>
/* ===== Home layout ===== */
.home {
  padding: 0 25px;
  display: grid;
  gap: 35px;
}

/* 공통 패널(흰 박스) */
.panel {
  background: #fff;
  border-radius: 22px;
  padding: 16px;
  box-shadow:
    0 18px 40px rgba(0, 0, 0, 0.1),
    0 2px 10px rgba(0, 0, 0, 0.06);
}

/* ===== Daily tone ===== */
.daily {
  width: 100%;
  height: 196px;
  background: linear-gradient(135deg, #f2e3f3 0%, #fbf0d2 45%, #f2f6ff 100%);
}

.daily-pill {
  display: inline-flex;
  align-items: center;
  height: 26px;
  padding: 0 12px;
  border-radius: 999px;
  border: 1px solid #3f5f73;
  background: rgba(255, 255, 255, 0.92);
  font-size: 10px;
  font-weight: 600;
  margin-bottom: 12px;
}

.daily-inner {
  display: grid;
  grid-template-columns: 1fr 92px;
}

.daily-text h2 {
  margin: 0 0 8px;
  font-size: 20px;
  font-weight: 700;
  color: #6969ad;
}

.daily-text p {
  margin: 0 0 16px;
  font-size: 12px;
  line-height: 1.45;
  font-weight: 500;
}

.daily-text b {
  color: #6868ab;
  font-weight: 700;
}

.daily-actions {
  width: 85px;
  height: 41px;
  background-color: #f2f2ee;
  box-shadow: inset 0px 0px 4px 0px rgba(0, 0, 0, 0.25);
  border-radius: 50px;
  display: flex;
  gap: 10px;
  align-items: center;
}

.icon-btn {
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}

.icon-btn.big {
  width: 41px;
  height: 41px;
  padding-left: 3px;
  border-radius: 999px;
  background: rgba(95, 96, 170, 0.95);
}

.daily-btn {
  padding-right: 15px;
}

.icon-btn img {
  width: 18px;
  height: 18px;
}

.icon-btn.big img {
  filter: brightness(10);
  opacity: 0.95;
}

.daily-swatch {
  width: 60px;
  height: 105px;
  margin-left: 15px;
  border-radius: 29.5px;
  background: #6868ab;
  border: 3px solid #ffffff;
  box-shadow: 0px 0px 4px 0px rgba(0, 0, 0, 0.25);
}

/* ===== Spectrum ===== */
.spec-track {
  border-radius: 18px;
  background: transparent;
  overflow: visible;
}

.spec-row {
  display: flex;
  background: #fff;
  gap: 14px;

  overflow-x: auto;
  overflow-y: hidden;
  -webkit-overflow-scrolling: touch;
  scroll-snap-type: x mandatory;

  padding: 20px 16px;
  margin: -20px -16px;
  border-radius: 18px;
}

.spec-row::-webkit-scrollbar {
  height: 0;
}

.spec-row.is-dragging {
  scroll-snap-type: none;
}

/* 패널 헤더 */
.panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.panel h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  letter-spacing: -0.2px;
}

.hint,
.more {
  font-size: 10px;
  font-weight: 500;
  text-decoration: none;
  color: inherit;
}

.spec-card {
  flex: 0 0 268px;
  border-radius: 17px;
  background: #fff;
  box-shadow: 0px 0px 21.3px -3px rgba(0, 0, 0, 0.25);
  text-decoration: none;
  overflow: hidden;
  border: 1px solid rgba(0, 0, 0, 0.05);
  color: inherit;
}

.spec-color {
  height: 90px;
  background: var(--c, #3fb9c8);
  border-radius: 17px 17px 0 0;
  border: 4px solid #fff;
}

.spec-body {
  background: #fff;
  padding: 12px 14px 14px;
}

.spec-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.spec-code {
  font-size: 16px;
  font-weight: 700;
}

.mini-add {
  width: 26px;
  height: 26px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}

.mini-add img {
  width: 14px;
  height: 14px;
}

.spec-name {
  margin-top: 2px;
  font-size: 22px;
  font-weight: 700;
  letter-spacing: -0.4px;
}

/* ===== Palette Log ===== */
.log-list {
  display: grid;
  gap: 14px;
}

.log-item {
  display: block;
  border-radius: 18px;
  padding: 14px 16px;
  background: var(--bg, #b7cc1a);
  color: #fff;
  text-decoration: none;
  box-shadow: 0 18px 30px rgba(0, 0, 0, 0.18);
}

.log-top {
  font-size: 10px;
  font-weight: 700;
  opacity: 0.9;
}

.log-main {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 4px;
  text-decoration: underline;
  text-underline-offset: 5px;
}

.log-main strong {
  font-size: 26px;
  font-weight: 700;
  letter-spacing: -0.6px;
  line-height: 1;
}

.chev {
  width: 10px;
  height: 10px;
  opacity: 0.9;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.chev img {
  width: 10px;
  height: 10px;
}

.log-item.is-dark .chev img {
  filter: brightness(0) invert(1);
}

.log-item.is-light .chev img {
  filter: none;
}

.log-sub {
  margin-top: 6px;
  font-size: 12px;
  font-weight: 900;
  opacity: 0.9;
}

/* ===== Echo Notes ===== */
.dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  background: #7b56d7;
  display: inline-block;
  margin-right: 8px;
}

.echo-card {
  padding: 26px 8px 10px;
  text-align: center;
}

.echo-text {
  margin: 0 0 18px;
  font-size: 20px;
  font-weight: 600;
}

.echo-line {
  height: 1px;
  margin: 0 12px 12px;
}

.echo-date {
  margin: 0;
  font-size: 10px;
  font-weight: 600;
}
</style>
