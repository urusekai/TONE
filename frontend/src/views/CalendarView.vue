<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useCalendarStore, createDefaultEntry } from '@/stores/calendarStore';
import { useAuthStore } from '@/stores/auth';
import { usePlayerStore } from '@/stores/player';
import { usePaletteLogStore } from '@/stores/paletteLog';
import { useToastStore } from '@/stores/toast';
import { updateMyProfileColor } from '@/services/userService';
import { fetchTodayCalendarPlaylist } from '@/services/calendarService';
import { playPlaylistFirstTrack } from '@/services/playlistService';
import PlaylistActionControls from '@/components/PlaylistActionControls.vue';

const authStore = useAuthStore();
const player = usePlayerStore();
const paletteLog = usePaletteLogStore();
const toast = useToastStore();

// 아이콘/이미지
import prevIcon from '@/assets/icons/prev.svg';
import nextIcon from '@/assets/icons/arrow-right.svg';
import thumbTrack from '@/assets/images/thumb-track.png';

function formatKey(year, month, day) {
  // ✅ YYYY-MM-DD 형식
  return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
}

function getTodayKey() {
  const today = new Date();
  return formatKey(today.getFullYear(), today.getMonth() + 1, today.getDate());
}

const route = useRoute();
const router = useRouter();
const todayKey = getTodayKey();

/* =========================
   상태
========================= */
const viewDate = ref(new Date());
// ✅ YYYY-MM-DD 형식으로 초기화
const selectedKey = ref(
  formatKey(viewDate.value.getFullYear(), viewDate.value.getMonth() + 1, viewDate.value.getDate())
);
const memoText = ref('');
const isMonthLoading = ref(false);
const isSavingMemo = ref(false);
const todayFallbackEntry = ref(null);
const isEnterReady = ref(false);

const calendarStore = useCalendarStore();

/* =========================
   route.query.date 처리
   - MainView에서 goCalendar() 시 query로 dateKey 전달받음
   - 전달받으면 해당 날짜로 selectedKey 업데이트
========================= */
onMounted(() => {
  if (route.query.date) {
    const dateFromQuery = String(route.query.date);
    // YYYY-MM-DD 형식 검증
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateFromQuery)) {
      selectedKey.value = dateFromQuery;
    }
  }
});

onMounted(async () => {
  try {
    await paletteLog.load({ silent: true });
  } catch {
    // 팔레트 로그 로드 실패는 캘린더 진입을 막지 않음
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
  if (selectedKey.value === todayKey && todayFallbackEntry.value) {
    return todayFallbackEntry.value;
  }

  return calendarStore.calendarData[selectedKey.value] || createDefaultEntry();
});

const hasSelectedEntry = computed(
  () =>
    Boolean(calendarStore.calendarData[selectedKey.value]) ||
    (selectedKey.value === todayKey && Boolean(todayFallbackEntry.value))
);

/* =========================
   달력 계산
========================= */
const currentMonth = computed(() => viewDate.value.getMonth());
const currentYear = computed(() => viewDate.value.getFullYear());
const monthLabel = computed(() => `${String(currentMonth.value + 1).padStart(2, '0')}월`);
const currentMonthKey = computed(
  () => `${currentYear.value}-${String(currentMonth.value + 1).padStart(2, '0')}`
);

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
  selectedData,
  () => {
    // 선택된 날짜 데이터가 비동기로 로드될 때도 메모를 다시 맞춘다.
    memoText.value = selectedData.value.memo || '';
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
    const monthFirstKey = formatKey(currentYear.value, currentMonth.value + 1, 1);
    const monthLastKey = formatKey(currentYear.value, currentMonth.value + 1, daysInMonth.value);
    const todayInCurrentMonth = todayKey >= monthFirstKey && todayKey <= monthLastKey;

    selectedKey.value = todayInCurrentMonth ? todayKey : monthFirstKey;
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
      if (key === todayKey && todayFallbackEntry.value?.color) {
        map[day] = todayFallbackEntry.value.color;
        return;
      }
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
  const nextKey = formatKey(currentYear.value, currentMonth.value + 1, day);
  if (nextKey !== todayKey && !calendarStore.calendarData[nextKey]) return;
  selectedKey.value = nextKey;
}

function prevMonth() {
  viewDate.value = new Date(currentYear.value, currentMonth.value - 1, 1);
}
function nextMonth() {
  viewDate.value = new Date(currentYear.value, currentMonth.value + 1, 1);
}

async function loadMonthEntries() {
  isMonthLoading.value = true;
  isEnterReady.value = false;

  try {
    await calendarStore.loadMonth(currentMonthKey.value);
    if (currentMonthKey.value === todayKey.slice(0, 7) && !calendarStore.calendarData[todayKey]) {
      todayFallbackEntry.value = await fetchTodayCalendarPlaylist();
    } else {
      todayFallbackEntry.value = null;
    }
    await nextTick();
    requestAnimationFrame(() => {
      isEnterReady.value = true;
    });
  } catch (error) {
    todayFallbackEntry.value = null;
    const message = error instanceof Error ? error.message : '캘린더 기록을 불러오지 못했습니다.';
    window.alert(message);
  } finally {
    isMonthLoading.value = false;
  }
}

async function saveMemo() {
  if (isSavingMemo.value) return;

  isSavingMemo.value = true;

  try {
    await calendarStore.saveMemo(selectedKey.value, memoText.value, selectedData.value?.playlistId);
    if (selectedKey.value === todayKey) {
      todayFallbackEntry.value = null;
    }
    toast.show('캘린더에 저장되었습니다');
  } catch (error) {
    const message = error instanceof Error ? error.message : '메모 저장 중 오류가 발생했습니다.';
    window.alert(message);
  } finally {
    isSavingMemo.value = false;
  }
}

function handleMemoInput(event) {
  memoText.value = event.target.value;
}

async function handlePlayPlaylist() {
  const playlistId = String(selectedData.value?.playlistId || '').trim();
  if (!playlistId) return;

  try {
    await playPlaylistFirstTrack(player, playlistId, { autoplay: true, openMode: 'main' });
  } catch (error) {
    const message = error instanceof Error ? error.message : '플레이리스트 재생에 실패했습니다.';
    window.alert(message);
  }
}

function openPlaylistDetail() {
  const playlistId = String(selectedData.value?.playlistId || '').trim();
  if (!playlistId) return;

  router.push({
    path: '/playlist',
    query: { id: playlistId },
    state: { fromBottomTab: '/calendar' }
  });
}

async function handleTogglePaletteLog() {
  const playlistId = String(selectedData.value?.playlistId || '').trim();
  if (!playlistId) return;

  try {
    const result = await paletteLog.toggle(playlistId);
    if (!result) return;

    if (String(player.current_playlist.id || '') === playlistId) {
      player.patchCurrentPlaylist({
        saved: Boolean(result?.saved)
      });
    }
  } catch (error) {
    const message =
      error instanceof Error ? error.message : '팔레트 로그 저장 처리에 실패했습니다.';
    window.alert(message);
  }
}

const isChangingProfileColor = ref(false);

async function setProfileColor() {
  if (isChangingProfileColor.value) return;

  const color = selectedData.value?.color || '';
  if (!color) {
    window.alert('적용할 색상이 없습니다.');
    return;
  }

  isChangingProfileColor.value = true;

  try {
    const result = await updateMyProfileColor(color);
    const nextColor = result?.profileColor || color;
    authStore.setProfileColor(nextColor);
    toast.show('프로필이 설정되었습니다');
  } catch (error) {
    const message =
      error instanceof Error ? error.message : '프로필 색상 변경 중 오류가 발생했습니다.';
    window.alert(message);
  } finally {
    isChangingProfileColor.value = false;
  }
}

watch(
  currentMonthKey,
  () => {
    loadMonthEntries();
  },
  { immediate: true }
);

watch(
  () => calendarStore.calendarData,
  () => {
    if (route.query.date) return;

    const monthFirstKey = formatKey(currentYear.value, currentMonth.value + 1, 1);
    const monthLastKey = formatKey(currentYear.value, currentMonth.value + 1, daysInMonth.value);
    const todayInCurrentMonth = todayKey >= monthFirstKey && todayKey <= monthLastKey;

    selectedKey.value = todayInCurrentMonth ? todayKey : monthFirstKey;
  },
  { deep: true, immediate: true }
);
</script>

<template>
  <div>
    <main id="calendar" :class="{ 'is-enter-ready': isEnterReady }">
      <!-- 캘린더 카드 -->
      <section class="calendar-card" style="--calendar-card-delay: 0ms">
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

        <p v-if="isMonthLoading" class="calendar-state">캘린더 기록을 불러오는 중...</p>

        <div class="calendar-grid" id="calendarGrid">
          <div
            v-for="(day, idx) in calendarDays"
            :key="idx"
            class="calendar-day"
            :class="{
              selected: day && formatKey(currentYear, currentMonth + 1, day) === selectedKey,
              'is-disabled':
                day &&
                formatKey(currentYear, currentMonth + 1, day) !== todayKey &&
                !calendarStore.calendarData[formatKey(currentYear, currentMonth + 1, day)]
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
      <section class="daily-tone-card" style="--calendar-card-delay: 56ms">
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
              <PlaylistActionControls
                :saved="paletteLog.has(selectedData.playlistId)"
                :play-disabled="!hasSelectedEntry || !selectedData.playlistId"
                :save-disabled="
                  !hasSelectedEntry ||
                  !selectedData.playlistId ||
                  paletteLog.isPending(selectedData.playlistId)
                "
                @play="handlePlayPlaylist"
                @save="handleTogglePaletteLog"
              />
            </div>
          </div>

          <div class="tone-right">
            <button
              type="button"
              class="tone-color-preview"
              :style="{ background: selectedData.color }"
              :disabled="!hasSelectedEntry || !selectedData.playlistId"
              @click="openPlaylistDetail"
            ></button>
            <button
              type="button"
              class="btn-profile-set"
              :disabled="isChangingProfileColor || !hasSelectedEntry"
              @click="setProfileColor"
            >
              {{ isChangingProfileColor ? '변경중...' : '프로필 설정' }}
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
      <section class="memo-card" style="--calendar-card-delay: 112ms">
        <div class="memo-section">
          <div class="memo-header">
            <h4>오늘의 한마디</h4>
            <div class="memo-buttons">
              <button
                type="button"
                class="btn-primary"
                :disabled="isSavingMemo || !hasSelectedEntry"
                @click="saveMemo"
              >
                {{ isSavingMemo ? '저장중...' : '저장' }}
              </button>
            </div>
          </div>

          <div class="memo-box">
            <textarea
              id="memoInput"
              placeholder="오늘의 톤을 한 줄로 남겨주세요!"
              maxlength="50"
              :readonly="!hasSelectedEntry"
              :value="memoText"
              @input="handleMemoInput"
            ></textarea>
            <div class="memo-count">
              <span id="currentCount">{{ memoCount }}</span
              >/50
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>

<style scoped>
#calendar {
  justify-content: flex-start;
  overflow-y: auto;
}

#calendar .calendar-card,
#calendar .daily-tone-card,
#calendar .memo-card {
  opacity: 0;
  transform: translateY(12px);
  will-change: transform, opacity;
}

#calendar.is-enter-ready .calendar-card,
#calendar.is-enter-ready .daily-tone-card,
#calendar.is-enter-ready .memo-card {
  animation: calendar-card-enter 320ms ease both;
  animation-delay: var(--calendar-card-delay, 0ms);
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

#calendar .calendar-state {
  margin: 0 0 16px;
  text-align: center;
  font-size: 13px;
  color: #3f5f73;
}

#calendar .calendar-day {
  font-size: 10px;
  font-weight: 600;
  cursor: pointer;
}

#calendar .calendar-day.is-disabled {
  cursor: default;
  opacity: 0.45;
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
  margin-top: 15px;
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
  padding: 0;
  border-radius: 30px;
  background: #6b6aa8;
  border: none;
  border: 3px solid #fff;
  box-shadow: 0 0 4px rgba(0, 0, 0, 0.25);
  cursor: pointer;
}

#calendar .tone-color-preview:disabled {
  cursor: default;
}

#calendar .btn-profile-set {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  border: 1px solid #3f5f73;
  color: #3f5f73;
  padding: 6px 10px;
  border-radius: 20px;
  font-size: 10px;
  font-weight: 700;
  line-height: 1;
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

#calendar #memoInput[readonly] {
  cursor: default;
}

@keyframes calendar-card-enter {
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
