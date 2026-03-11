import { defineStore } from 'pinia';

let openFrameId = null;

export const useToastStore = defineStore('toast', {
  state: () => ({
    open: false,
    message: ''
  }),

  actions: {
    show(message) {
      const nextMessage = String(message || '').trim();
      if (!nextMessage) return;

      this.message = nextMessage;
      this.open = false;

      if (openFrameId !== null) {
        window.cancelAnimationFrame(openFrameId);
      }

      openFrameId = window.requestAnimationFrame(() => {
        openFrameId = null;
        this.open = true;
      });
    },

    close() {
      this.open = false;
    }
  }
});
