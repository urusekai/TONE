// src/stores/paletteLog.js
import { defineStore } from 'pinia';

const KEY = 'tone.paletteLog.items';

function load() {
  try {
    return JSON.parse(localStorage.getItem(KEY)) ?? [];
  } catch {
    return [];
  }
}
function save(items) {
  localStorage.setItem(KEY, JSON.stringify(items));
}

export const usePaletteLogStore = defineStore('paletteLog', {
  state: () => ({
    items: load(),

    // ✅ add 버튼 전역 바인딩용(스토어 내부 관리)
    _addBound: false,
    _addHandler: null
  }),

  getters: {
    has: (state) => (playlistId) => state.items.some((it) => it.playlistId === playlistId)
  },

  actions: {
    add(entry) {
      if (!entry?.playlistId) return;
      if (this.items.some((it) => it.playlistId === entry.playlistId)) return;

      this.items.unshift({
        ...entry,
        savedAt: Date.now()
      });

      save(this.items);
    },

    remove(playlistId) {
      this.items = this.items.filter((it) => it.playlistId !== playlistId);
      save(this.items);
    },

    toggle(entry) {
      if (!entry?.playlistId) return;
      if (this.has(entry.playlistId)) this.remove(entry.playlistId);
      else this.add(entry);
    },

    clear() {
      this.items = [];
      save(this.items);
    },

    /* =========================
       ✅ 여기부터 추가 (date 제거 버전)
    ========================= */
    bindAddButtons() {
      if (this._addBound) return;
      this._addBound = true;

      this._addHandler = (e) => {
        const btn = e.target.closest('[data-action="add-to-log"]');
        if (!btn) return;

        const root = btn.closest('[data-log-item]') || btn;

        const entry = {
          playlistId: root.dataset.playlistId || root.dataset.id,
          name: root.dataset.name || '',
          color: root.dataset.color || '',
          likes: root.dataset.likes || '0,000',
          plays: root.dataset.plays || '100 Plays'
        };

        if (!entry.playlistId) return;

        this.toggle(entry);

        // ✅ 버튼 상태 class (아이콘 교체는 다음 단계에서)
        btn.classList.toggle('is-added', this.has(entry.playlistId));
      };

      document.addEventListener('click', this._addHandler);
    },

    unbindAddButtons() {
      if (!this._addBound) return;
      document.removeEventListener('click', this._addHandler);
      this._addHandler = null;
      this._addBound = false;
    }
  }
});
