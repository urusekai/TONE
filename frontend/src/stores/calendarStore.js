import { defineStore } from 'pinia';
import { fetchCalendarEntries, saveCalendarEntry } from '@/services/calendarService';

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
      cover: null
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
    mergeEntry(entry, fallback = createDefaultEntry()) {
      return {
        ...fallback,
        ...entry,
        music: {
          ...fallback.music,
          ...(entry?.music || {})
        }
      };
    },

    setMonthEntries(entries) {
      const nextData = {};

      entries.forEach((entry) => {
        if (!entry?.entryDate) return;
        nextData[entry.entryDate] = this.mergeEntry(entry);
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

    async saveEntry(dateKey, payload = {}) {
      const prev = this.calendarData[dateKey] || createDefaultEntry();
      const requestBody = {
        entryDate: dateKey
      };

      if (Object.prototype.hasOwnProperty.call(payload, 'memo')) {
        requestBody.memo = payload.memo;
      }

      if (Object.prototype.hasOwnProperty.call(payload, 'playlistId')) {
        requestBody.playlistId = payload.playlistId;
      }

      const result = await saveCalendarEntry(requestBody);
      const entry = result?.entry;

      if (!entry?.entryDate) {
        throw new Error('저장된 캘린더 기록을 확인할 수 없습니다.');
      }

      this.calendarData[entry.entryDate] = this.mergeEntry(entry, prev);
      return this.calendarData[entry.entryDate];
    },

    async saveMemo(dateKey, memo, playlistId = null) {
      return await this.saveEntry(dateKey, {
        memo,
        playlistId
      });
    },

    async saveTone(dateKey, playlistId) {
      return await this.saveEntry(dateKey, {
        playlistId
      });
    }
  }
});
