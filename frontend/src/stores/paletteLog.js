// src/stores/paletteLog.js
import { defineStore } from 'pinia';

export const usePaletteLogStore = defineStore('paletteLog', {
  state: () => ({
    // 최신이 위로 쌓이는 형태
    items: []
  }),

  getters: {
    has: (state) => (playlistId) => state.items.some((it) => it.playlistId === playlistId)
  },

  actions: {
    add(entry) {
      // 중복 방지
      if (this.items.some((it) => it.playlistId === entry.playlistId)) return;

      this.items.unshift({
        ...entry,
        savedAt: Date.now()
      });
    },

    remove(playlistId) {
      this.items = this.items.filter((it) => it.playlistId !== playlistId);
    },

    toggle(entry) {
      if (this.has(entry.playlistId)) this.remove(entry.playlistId);
      else this.add(entry);
    }
  }
});
