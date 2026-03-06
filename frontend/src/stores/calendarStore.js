import { defineStore } from 'pinia';
import { fetchCalendarEntries, saveCalendarEntry } from '@/services/calendarService';

/*
  날짜별 데이터를 저장하는 객체 (Record)

  ✅ key: 'YYYY-MM-DD'  (예: '2026-02-01')
  - 연도를 포함하여 연도 변경 시에도 데이터 분리

  value 예시:
  {
    name: 'Bordeaux',
    number: '17-1710',
    color: '#97637c',
    memo: '',
    music: {
      title: '',
      artist: '',
      cover: ''
    }
  }

  ⚠️ NOTE (추후 변경 가능)
  - 음악 데이터를 API에서 가져올 수도 있음
  - playlistId만 저장하도록 변경될 수도 있음
*/
// ✅ export하여 외부에서도 사용 가능
export function createDefaultEntry() {
  return {
    id: null,
    playlistId: null,
    name: '기록 없음',
    number: '00-0000',
    color: '#d9d9d9',
    memo: '',
    music: {
      title: 'Title',
      artist: 'Artist',
      cover: null // ✅ null로 명시적 처리 (빈 문자열 대신)
    }
  };
}

export const useCalendarStore = defineStore('calendar', {
  state: () => ({
    calendarData: {},
    currentMonth: '',
    isLoading: false
  }),

  actions: {
    setMonthEntries(entries) {
      const nextData = {};

      entries.forEach((entry) => {
        if (!entry?.entryDate) return;

        nextData[entry.entryDate] = {
          ...createDefaultEntry(),
          ...entry,
          music: {
            ...createDefaultEntry().music,
            ...(entry.music || {})
          }
        };
      });

      this.calendarData = nextData;
    },

    async loadMonth(month) {
      this.isLoading = true;

      try {
        const result = await fetchCalendarEntries(month);
        this.currentMonth = result?.month || month;
        this.setMonthEntries(Array.isArray(result?.entries) ? result.entries : []);
      } finally {
        this.isLoading = false;
      }
    },

    async saveMemo(dateKey, memo, playlistId = null) {
      const prev = this.calendarData[dateKey] || createDefaultEntry();
      const result = await saveCalendarEntry({
        entryDate: dateKey,
        memo,
        playlistId
      });
      const entry = result?.entry;

      if (!entry?.entryDate) {
        throw new Error('저장된 캘린더 기록을 확인할 수 없습니다.');
      }

      this.calendarData[entry.entryDate] = {
        ...prev,
        ...entry,
        music: {
          ...prev.music,
          ...(entry.music || {})
        },
        memo
      };
    }
  }
});
