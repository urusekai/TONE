import { defineStore } from 'pinia';

const STORAGE_KEY = 'tone_calendar_data';

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
    // ✅ 날짜별 기록
    calendarData: {},
    // ✅ loadFromLocalStorage 한 번만 실행하는 플래그
    _loaded: false
  }),

  actions: {
    /*
      앱 시작 시 localStorage 데이터 불러오기

      ⚠️ NOTE
      - main.js에서 1번 호출 (권장)
      - 한 번만 로드됨 (_loaded 플래그 사용)
      - CalendarView.onMounted에서도 호출되지만 플래그로 인해 1회만 실행
    */
    loadFromLocalStorage() {
      // ✅ 플래그 기반 한 번만 로드
      if (this._loaded) {
        return;
      }

      const saved = localStorage.getItem(STORAGE_KEY);
      if (saved) {
        try {
          const parsed = JSON.parse(saved);
          this.calendarData = typeof parsed === 'object' && parsed !== null ? parsed : {};
        } catch (e) {
          // 저장된 값이 깨졌을 때 안전장치
          console.warn('[calendarStore] localStorage 파싱 실패:', e);
          this.calendarData = {};
        }
      }

      this._loaded = true;  // ✅ 로드 완료 표시
    },

    /*
      localStorage 저장

      ⚠️ NOTE
      추후 로그인 시스템이 생기면 서버 DB 저장 방식으로 변경될 수 있음
    */
    saveToLocalStorage() {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(this.calendarData));
    },

    /*
      날짜 데이터 저장 / 업데이트
      - MainView에서 daily tone 저장할 때 사용

      payload 예시:
      { name, number, color, music: { title, artist, cover } }
    */
    saveDailyTone(dateKey, payload) {
      const prev = this.calendarData[dateKey] || createDefaultEntry();

      this.calendarData[dateKey] = {
        ...prev,
        ...payload,
        // music는 객체라서 안전하게 merge
        music: { ...prev.music, ...(payload?.music || {}) }
      };

      this.saveToLocalStorage();
    },

    /*
      메모 저장
      - CalendarView에서 memo 저장 버튼 클릭 시 사용
      - ✅ 명시적으로 필드 지정 (유지/변경 구분)
    */
    saveMemo(dateKey, memo) {
      const prev = this.calendarData[dateKey] || createDefaultEntry();

      // ✅ 명시적으로 어떤 필드를 유지하는지 표기
      this.calendarData[dateKey] = {
        // 유지할 필드
        name: prev.name,
        number: prev.number,
        color: prev.color,
        music: prev.music,
        // 변경할 필드
        memo
      };

      this.saveToLocalStorage();
    }
  }
});
