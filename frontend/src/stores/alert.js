import { defineStore } from 'pinia';

export const useAlertStore = defineStore('alert', {
  state: () => ({
    open: false,
    title: '알림',
    message: '',
    confirmText: '확인'
  }),

  actions: {
    show(message, options = {}) {
      const nextMessage = String(message || '').trim();
      if (!nextMessage) return;

      this.title = String(options?.title || '알림').trim() || '알림';
      this.confirmText = String(options?.confirmText || '확인').trim() || '확인';
      this.message = nextMessage;
      this.open = true;
    },

    close() {
      this.open = false;
    }
  }
});
