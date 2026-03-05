<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { useCalendarStore } from '@/stores/calendarStore';

const router = useRouter();
const calendarStore = useCalendarStore();

/* =========================
   ✅ 기존 dailyTonePayload / spectrumPayloads는 너 코드 그대로 둬도 됨
   여기선 Echo Notes 동기화를 위해 activeTone만 새로 둠
========================= */
const dailyTonePayload = {
  name: 'Very Peri',
  number: '17-3938',
  color: '#6667AB',
  music: { title: 'Title', artist: 'Artist', cover: null }
};

const spectrumPayloads = {
  bordeaux: {
    name: 'Bordeaux',
    number: '17-1710',
    color: '#97637c',
    music: { title: 'Title', artist: 'Artist', cover: null }
  },
  scarletSmile: {
    name: 'Scarlet Smile',
    number: '19-1558',
    color: '#9f2336',
    music: { title: 'Title', artist: 'Artist', cover: null }
  },
  pinkLemonade: {
    name: 'Pink Lemonade',
    number: '16-1735',
    color: '#ef6f8e',
    music: { title: 'Title', artist: 'Artist', cover: null }
  }
};

/* =========================
   ✅ Echo Notes: 현재 선택된 톤(=dot 색 기준)
   - 처음엔 dailyTonePayload
   - goCalendar(payload) 호출 시 activeTone도 갱신해서 동기화
========================= */
const activeTone = ref({ ...dailyTonePayload });

/* =========================
   ✅ 같은 컬러 유저 한마디 더미 데이터(색상별로 묶어둠)
   - 실제 API 붙일 때: activeTone.color로 서버에서 필터된 5개 받아오면 됨
========================= */
const echoPoolByColor = {
  '#6667AB': [
    '오늘 톤이랑 플레이리스트가 진짜 잘 맞아.',
    '이 색 보면 마음이 좀 안정되는 느낌!',
    '오늘 하루는 이 톤으로 기록하고 싶다.',
    '같은 컬러 선택한 사람들 많아서 신기해.',
    '이 톤 덕분에 집중이 잘 됐어.'
  ],
  '#97637c': [
    '보르도 톤 선택하니까 분위기 확 바뀜.',
    '오늘은 무드 있게 가고 싶었어.',
    '색이 깊어서 감정이 정리되는 느낌.',
    '이 컬러랑 재즈 조합 너무 좋다.',
    '노을 같은 색이라 계속 보게 돼.'
  ],
  '#9f2336': [
    '스칼렛 계열은 들으면 힘이 나!',
    '오늘은 좀 강하게 가고 싶어서 이걸로.',
    '이 톤은 진짜 주인공 느낌이다.',
    '플레이리스트 텐션이랑 딱 맞음.',
    '기분 전환 제대로 됐다.'
  ],
  '#ef6f8e': [
    '핑크 레몬에이드 톤 너무 귀엽다.',
    '오늘은 가볍고 산뜻하게!',
    '색이 밝아서 하루가 부드러워졌어.',
    '이 톤 선택한 사람 많을 듯 ㅋㅋ',
    '노래도 달달해서 좋아.'
  ]
};

/* =========================
   ✅ Echo Notes 로테이션 상태
========================= */
const echoNotes = ref([]); // 현재 톤 기준 5개
const echoIndex = ref(0);
const echoText = ref('');
const echoVisible = ref(true);

let echoTimer = null;

const echoDotStyle = computed(() => ({
  background: activeTone.value?.color || '#7b56d7'
}));

function buildEchoNotesByTone(color) {
  const pool = echoPoolByColor[color] || echoPoolByColor['#6667AB'];
  // 5개만 사용 (pool이 5개 이상이라고 가정)
  echoNotes.value = pool.slice(0, 5).map((text, i) => ({
    id: `${color}_${i}`,
    text
  }));

  echoIndex.value = 0;
  echoText.value = echoNotes.value[0]?.text ?? '';
}

function startEchoRotation() {
  stopEchoRotation();
  if (!echoNotes.value.length) return;

  echoVisible.value = true;

  echoTimer = setInterval(() => {
    // 1) fade out
    echoVisible.value = false;

    // 2) 텍스트 교체 후 fade in
    window.setTimeout(() => {
      echoIndex.value = (echoIndex.value + 1) % echoNotes.value.length;
      echoText.value = echoNotes.value[echoIndex.value]?.text ?? '';
      echoVisible.value = true;
    }, 260);
  }, 2600);
}

function stopEchoRotation() {
  if (echoTimer) {
    clearInterval(echoTimer);
    echoTimer = null;
  }
}

/* =========================
   플레이리스트 경로 생성 / 플레이어 열기
========================= */
const playlistTo = (id) => ({ path: `/playlist/${id}` });
const openMainPlayerDaily = () => router.push({ path: '/player', query: { tone: 'daily' } });

/* =========================
   날짜키 유틸 (YYYY-MM-DD)
========================= */
function getTodayKey() {
  const today = new Date();
  const yyyy = today.getFullYear();
  const mm = String(today.getMonth() + 1).padStart(2, '0');
  const dd = String(today.getDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
}

/* =========================
   ✅ goCalendar(payload) 교체 버전
   - 캘린더 저장 + 이동
   - ✅ activeTone도 함께 갱신해서 dot/echoNotes 즉시 동기화
========================= */
function goCalendar(payload = dailyTonePayload) {
  const dateKey = getTodayKey();

  // 1) 캘린더 저장
  calendarStore.saveDailyTone(dateKey, payload);

  // 2) ✅ 메인 Echo Notes 동기화(톤 변경)
  activeTone.value = { ...payload };
  buildEchoNotesByTone(activeTone.value.color);
  startEchoRotation();

  // 3) 캘린더로 이동
  router.push({ path: '/calendar', query: { date: dateKey } });
}

/* =========================
   마운트 시 초기 세팅
========================= */
onMounted(() => {
  buildEchoNotesByTone(activeTone.value.color);
  startEchoRotation();
});

onBeforeUnmount(() => {
  stopEchoRotation();
});
</script>

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

            <button
              class="icon-btn daily-btn"
              type="button"
              aria-label="save"
              @click="goCalendar(dailyTonePayload)"
            >
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
                  @click.capture.stop.prevent="goCalendar(spectrumPayloads.bordeaux)"
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
                  @click.capture.stop.prevent="goCalendar(spectrumPayloads.scarletSmile)"
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
                  @click.capture.stop.prevent="goCalendar(spectrumPayloads.pinkLemonade)"
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
        <h3><span class="dot" :style="echoDotStyle"></span>Echo Notes</h3>
        <span class="hint">같은 데일리 컬러 유저들의 한마디</span>
      </div>

      <div class="echo-card">
        <!-- ✅ 텍스트만 페이드 전환 -->
        <p class="echo-text" :class="{ 'is-hidden': !echoVisible }">
          {{ echoText }}
        </p>

        <div class="echo-line"></div>

        <!-- 날짜 표시: 매일 갱신 -->
        <p class="echo-date">{{ echoDate }}</p>
      </div>
    </section>
  </main>
</template>

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
  box-shadow: inset 0px 0px 4px 0px rgba(0, 0, 0, 0.25);
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
  width: 18px;
  height: 18px;
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
  background: #7b56d7; /* 기본값(실제는 :style로 덮임) */
  display: inline-block;
  margin-right: 8px;
}

.echo-card {
  padding: 26px 8px 10px;
  text-align: center;
  min-height: 110px;
}

.echo-text {
  margin: 0 0 18px;
  font-size: 16px;
  font-weight: 600;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  word-break: keep-all;
  line-clamp: 2;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;

  /* ✅ 텍스트만 fade */
  opacity: 1;
  transition: opacity 250ms ease;
}

.echo-text.is-hidden {
  opacity: 0;
}

.echo-line {
  height: 1px;
  margin: 0 12px 12px;
  background: rgba(0, 0, 0, 0.08);
}

.echo-date {
  margin: 0;
  font-size: 10px;
  font-weight: 600;
  opacity: 0.8;
}
</style>
