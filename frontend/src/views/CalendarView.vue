<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';

// 스토어
// ✅ createDefaultEntry 동시 import
import { useUiStore } from '@/stores/uiStore';
import { useCalendarStore, createDefaultEntry } from '@/stores/calendarStore';

// 아이콘/이미지
import prevIcon from '@/assets/icons/prev.svg';
import nextIcon from '@/assets/icons/arrow-right.svg';
import pauseIcon from '@/assets/icons/pause.svg';
import addIcon from '@/assets/icons/add.svg';
import thumbTrack from '@/assets/images/thumb-track.png';

const route = useRoute();

function formatKey(year, month, day) {
  // ✅ YYYY-MM-DD 형식
  return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
}

/* =========================
   상태
========================= */
const viewDate = ref(new Date());
// ✅ YYYY-MM-DD 형식으로 초기화
const selectedKey = ref(
  formatKey(viewDate.value.getFullYear(), viewDate.value.getMonth() + 1, viewDate.value.getDate())
);
const isEditing = ref(false);
const memoText = ref('');

const uiStore = useUiStore();
const calendarStore = useCalendarStore();

/* =========================
   route.query.date 처리
   - MainView에서 goCalendar() 시 query로 dateKey 전달받음
   - 전달받으면 해당 날짜로 selectedKey 업데이트
========================= */
onMounted(() => {
  // ✅ localStorage 데이터 확실히 로드 (새로고침 시 대비)
  calendarStore.loadFromLocalStorage();

  if (route.query.date) {
    const dateFromQuery = String(route.query.date);
    // YYYY-MM-DD 형식 검증
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateFromQuery)) {
      selectedKey.value = dateFromQuery;
    }
  }
});

/* =========================
   데이터
========================= */
/*
  선택된 날짜 데이터

  ✅ createDefaultEntry 사용으로 defaultData 통일
  - store의 기본값과 일치하여 예측 가능한 동작
*/
const selectedData = computed(() => {
  // ✅ createDefaultEntry 사용으로 defaultData 통일
  return calendarStore.calendarData[selectedKey.value] || createDefaultEntry();
});

/* =========================
   달력 계산
========================= */
const currentMonth = computed(() => viewDate.value.getMonth());
const currentYear = computed(() => viewDate.value.getFullYear());
const monthLabel = computed(() => `${String(currentMonth.value + 1).padStart(2, '0')}월`);

const daysInMonth = computed(() =>
  new Date(currentYear.value, currentMonth.value + 1, 0).getDate()
);
const firstDayOffset = computed(() => new Date(currentYear.value, currentMonth.value, 1).getDay());

const calendarDays = computed(() => {
  const arr = [];
  for (let i = 0; i < firstDayOffset.value; i++) arr.push(null);
  for (let d = 1; d <= daysInMonth.value; d++) arr.push(d);
  return arr;
});

/* =========================
   선택된 데이터 동기화
========================= */
watch(
  selectedKey,
  () => {
    // ✅ memoText 안전 처리
    memoText.value = selectedData.value.memo || '';
    isEditing.value = false;
  },
  { immediate: true }
);

/*
  ✅ 월 변경 시 selectedKey 업데이트
  - prevMonth() / nextMonth() 호출 후
  - viewDate가 변경되면 자동으로 selectedKey 다시 계산
*/
watch(
  () => `${currentYear.value}-${currentMonth.value}`,
  () => {
    // 현재 월의 1일로 selectedKey 업데이트
    selectedKey.value = formatKey(currentYear.value, currentMonth.value + 1, 1);
  }
);

const memoCount = computed(() => memoText.value.length);

/* =========================
   최적화: 날짜별 day-dot 데이터 캐싱
   - formatKey() 반복 호출 제거
   - 각 day에 대한 색상을 미리 계산
========================= */
const dayColorMap = computed(() => {
  const map = {};
  calendarDays.value.forEach((day) => {
    if (day) {
      const key = formatKey(currentYear.value, currentMonth.value + 1, day);
      map[day] = calendarStore.calendarData[key]?.color || '#d9d9d9';
    }
  });
  return map;
});

/* =========================
   이벤트
========================= */
function selectDate(day) {
  if (!day) return;
  selectedKey.value = formatKey(currentYear.value, currentMonth.value + 1, day);
}

function prevMonth() {
  viewDate.value = new Date(currentYear.value, currentMonth.value - 1, 1);
}
function nextMonth() {
  viewDate.value = new Date(currentYear.value, currentMonth.value + 1, 1);
}

function startEdit() {
  isEditing.value = true;
}

/* ✅ 메모 저장: store로만 */
function saveMemo() {
  calendarStore.saveMemo(selectedKey.value, memoText.value);
  isEditing.value = false;
  alert('저장되었습니다!');
}

function goPlaylist() {
  window.location.href = './playlist.html';
}

/* ✅ 프로필 컬러 설정 + toast 표시 */
function setProfileColor() {
  const color = selectedData.value?.color || '';
  if (color && color !== '#d9d9d9') {
    // 기본값 제외
    uiStore.setAvatarColor(color);
    showToast(); // ✅ toast 표시
  }
}

/* Toast UI */
const toastVisible = ref(false);

function showToast() {
  toastVisible.value = true;
  setTimeout(() => {
    toastVisible.value = false;
  }, 2000);
}
</script>

<template>
  <div>
    <main id="calendar">
      <!-- 캘린더 카드 -->
      <section class="calendar-card">
        <div class="calendar-header">
          <button type="button" id="prevMonth" @click="prevMonth">
            <img :src="prevIcon" alt="이전달" />
          </button>

          <!-- ✅ CSS가 #monthTitle에 걸려있어서 id 유지 -->
          <h2 id="monthTitle">{{ monthLabel }}</h2>

          <button type="button" id="nextMonth" @click="nextMonth">
            <img :src="nextIcon" alt="다음달" />
          </button>
        </div>

        <div class="calendar-grid" id="calendarGrid">
          <div
            v-for="(day, idx) in calendarDays"
            :key="idx"
            class="calendar-day"
            :class="{
              selected: day && formatKey(currentYear, currentMonth + 1, day) === selectedKey
            }"
            @click="selectDate(day)"
          >
            <template v-if="day">
              <span>{{ String(day).padStart(2, '0') }}</span>
              <div
                class="day-dot"
                :style="{
                  backgroundColor: dayColorMap[day]
                }"
              />
            </template>
          </div>
        </div>
      </section>

      <!-- 데일리 팬톤 카드 -->
      <section class="daily-tone-card">
        <div class="tone-main-row">
          <div class="tone-left">
            <div class="date-badge">{{ selectedKey.slice(5).replace('-', '.') }}</div>

            <div class="tone-text">
              <h3>{{ selectedData.name }}</h3>
              <p class="pantone-num">
                {{
                  selectedData.number?.includes('팬톤')
                    ? selectedData.number
                    : `${selectedData.number || '00-0000'}(팬톤 컬러넘버)`
                }}
              </p>
            </div>

            <div class="tone-controls">
              <button type="button" class="btn-play-pause" @click="goPlaylist">
                <img :src="pauseIcon" alt="재생/일시정지" />
              </button>
              <button type="button" class="btn-add-list">
                <img :src="addIcon" alt="추가" />
              </button>
            </div>
          </div>

          <div class="tone-right">
            <div class="tone-color-preview" :style="{ background: selectedData.color }"></div>
            <button type="button" class="btn-profile-set" @click="setProfileColor">
              프로필 설정
            </button>
          </div>
        </div>

        <div class="tone-music-info">
          <div class="music-thumb">
            <img :src="selectedData.music.cover || thumbTrack" alt="앨범아트" />
          </div>
          <div class="music-text">
            <span class="music-title">{{ selectedData.music.title }}</span>
            <span class="music-artist">{{ selectedData.music.artist }}</span>
          </div>
        </div>
      </section>

      <!-- 메모 영역 -->
      <section class="memo-card">
        <div class="memo-section">
          <div class="memo-header">
            <h4>기록 메모</h4>
            <div class="memo-buttons">
              <button type="button" class="btn-outline" @click="startEdit">
                {{ isEditing ? '수정 중' : '수정' }}
              </button>
              <button type="button" class="btn-primary" @click="saveMemo">저장</button>
            </div>
          </div>

          <div class="memo-box">
            <textarea
              id="memoInput"
              placeholder="오늘의 톤을 한 줄로 남겨주세요!"
              maxlength="50"
              :readonly="!isEditing"
              v-model="memoText"
            ></textarea>
            <div class="memo-count">
              <span id="currentCount">{{ memoCount }}</span
              >/50
            </div>
          </div>
        </div>
      </section>
    </main>
    <div v-if="toastVisible" class="toast">프로필 컬러가 변경되었습니다</div>
  </div>
</template>

<style scoped>
#calendar {
  justify-content: flex-start;
  overflow-y: auto;
}

/* ------------------ CALENDAR HEADER ------------------ */
#calendar .calendar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  margin-bottom: 25px;
  width: 100%;
}

#calendar .calendar-header button {
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  transition: opacity 0.2s;
}

#calendar .calendar-header button img {
  width: 8px;
  height: auto;
  display: block;
}

#calendar #monthTitle {
  margin-bottom: 0;
  line-height: 1;
  min-width: 60px;
  text-align: center;
  color: #3f5f73;
  font-size: 16px;
  font-weight: 700;
}

/* ------------------ CALENDAR ------------------ */
#calendar .calendar-card {
  background: #fff;
  border-radius: 17px;
  padding: 20px;
  width: 100%;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

#calendar .calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 15px;
  text-align: center;
}

#calendar .calendar-day {
  font-size: 10px;
  font-weight: 600;
  cursor: pointer;
}

#calendar .day-dot {
  width: 15px;
  height: 15px;
  border-radius: 50%;
  background: #d9d9d9;
  margin: 6px auto 0;
  border: 2px solid transparent;
  transition: all 0.2s ease;
}

#calendar .calendar-day.selected .day-dot {
  border-color: #3f5f73;
  transform: scale(1.1);
}

#calendar .active-dark .day-dot {
  background: #3f5f73;
}

#calendar .tone-blue .day-dot {
  background: #6faed9;
}

#calendar .tone-lightblue .day-dot {
  background: #8fd8ec;
}

#calendar .tone-pink .day-dot {
  background: #fea1b8;
}

/* ------------------ DAILY CARD ------------------ */
#calendar .daily-tone-card {
  background: #fff;
  border-radius: 17px;
  padding: 20px;
  margin-top: 24px;
  width: 100%;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

#calendar .tone-main-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
}

#calendar .tone-left {
  flex: 1;
}

#calendar .date-badge {
  display: inline-block;
  background: #3f5f73;
  color: #fff;
  padding: 6px 18px;
  border-radius: 15px;
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 15px;
}

#calendar .tone-text h3 {
  font-size: 20px;
  color: #3f5f73;
  font-weight: 800;
  margin-bottom: 4px;
}

#calendar .pantone-num {
  font-size: 15px;
  color: #3f5f73;
  font-weight: 600;
}

#calendar .tone-controls {
  display: flex;
  align-items: center;
  background: #f2f2ee;
  border-radius: 25px;
  width: fit-content;
  padding: 4px;
  margin-top: 15px;
  box-shadow: inset 0 0 4px rgba(0, 0, 0, 0.25);
}

#calendar .btn-play-pause {
  background: #3f5f73;
  border: none;
  border-radius: 50%;
  width: 36px;
  height: 36px;
  display: flex;
  justify-content: center;
  align-items: center;
  cursor: pointer;
}

#calendar .btn-play-pause img {
  width: 14px;
  filter: brightness(0) invert(1);
}

#calendar .btn-add-list {
  background: transparent;
  border: none;
  padding: 0 12px;
  cursor: pointer;
}

#calendar .btn-add-list img {
  width: 14px;
  height: 14px;
}

#calendar .tone-right {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

#calendar .tone-color-preview {
  width: 59px;
  height: 110px;
  border-radius: 30px;
  background: #6b6aa8;
  border: 3px solid #fff;
  box-shadow: 0 0 4px rgba(0, 0, 0, 0.25);
}

#calendar .btn-profile-set {
  background: #fff;
  border: 1px solid #3f5f73;
  color: #3f5f73;
  padding: 6px 10px;
  border-radius: 20px;
  font-size: 10px;
  font-weight: 700;
  margin-top: 5px;
}

#calendar .tone-music-info {
  background: #f2f2ee;
  border-radius: 20px;
  padding: 15px 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: inset 0 0 4px rgba(0, 0, 0, 0.25);
}

#calendar .music-thumb {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  overflow: hidden;
}

#calendar .music-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

#calendar .music-text {
  display: flex;
  flex-direction: column;
}

#calendar .music-title {
  font-weight: 800;
  color: #3f5f73;
  font-size: 15px;
}

#calendar .music-artist {
  font-size: 12px;
  color: #777;
  margin-top: 2px;
}

/* ------------------ MEMO ------------------ */
#calendar .memo-card {
  background: #fff;
  border-radius: 17px;
  padding: 20px;
  margin-top: 24px;
  margin-bottom: 20px;
  width: 100%;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

#calendar .memo-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

#calendar .memo-header h4 {
  font-size: 15px;
  font-weight: 700;
}

#calendar .memo-buttons {
  display: flex;
  gap: 8px;
}

#calendar .btn-outline,
#calendar .btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 54px;
  height: 28px;
  font-size: 12px;
  font-weight: 600;
  border-radius: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

#calendar .btn-outline {
  background: #fff;
  border: 1px solid #3f5f73;
  color: #3f5f73;
}

#calendar .btn-primary {
  background: #3f5f73;
  border: 1px solid #3f5f73;
  color: #fff;
}

#calendar .memo-box {
  background: #f2f2ee;
  border-radius: 12px;
  padding: 12px;
  position: relative;
}

#calendar .memo-box textarea {
  width: 100%;
  height: 80px;
  background: transparent;
  border: none;
  resize: none;
  font-size: 13px;
  outline: none;
}

#calendar #memoInput {
  color: #3f5f73;
  font-weight: 700;
}

#calendar #memoInput:not([readonly]) {
  background-color: #e9e9e1;
  border-radius: 8px;
  box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.05);
  color: #3f5f73d2;
}

/* Toast */
.toast {
  position: fixed;
  bottom: 90px;
  left: 50%;
  transform: translateX(-50%);
  background: #3f5f73;
  color: white;
  padding: 10px 18px;
  border-radius: 20px;
  font-size: 13px;
  animation: toastFade 2s ease forwards;
}

@keyframes toastFade {
  0% {
    opacity: 0;
    transform: translate(-50%, 20px);
  }
  20% {
    opacity: 1;
    transform: translate(-50%, 0);
  }
  80% {
    opacity: 1;
  }
  100% {
    opacity: 0;
  }
}
</style>
