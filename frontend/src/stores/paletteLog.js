import { defineStore } from 'pinia';
import { apiRequest } from '@/services/httpClient';
import { useToastStore } from '@/stores/toast';

function isLoginRequiredError(error) {
  return error instanceof Error && error.message.includes('로그인이 필요합니다.');
}

export const usePaletteLogStore = defineStore('paletteLog', {
  state: () => ({
    paletteLogs: [],
    isLoading: false,
    isLoaded: false,
    pendingMap: {}
  }),

  getters: {
    has: (state) => (playlistId) =>
      state.paletteLogs.some((item) => String(item.playlist_id) === String(playlistId)),
    isPending: (state) => (playlistId) => Boolean(state.pendingMap[String(playlistId)])
  },

  actions: {
    setPending(playlistId, value) {
      this.pendingMap = {
        ...this.pendingMap,
        [String(playlistId)]: value
      };
    },

    async load(options = {}) {
      const { force = false, silent = false } = options;

      if (this.isLoaded && !force) {
        return this.paletteLogs;
      }

      if (!silent) {
        this.isLoading = true;
      }

      try {
        const result = await apiRequest(
          '/api/palette-logs/list.php',
          {},
          '팔레트 로그를 불러오지 못했습니다.'
        );

        this.paletteLogs = Array.isArray(result?.paletteLogs) ? result.paletteLogs : [];
      } catch (error) {
        if (isLoginRequiredError(error)) {
          this.paletteLogs = [];
        } else {
          throw error;
        }
      } finally {
        this.isLoaded = true;

        if (!silent) {
          this.isLoading = false;
        }
      }

      return this.paletteLogs;
    },

    async toggle(playlistId) {
      const normalizedPlaylistId = String(playlistId || '').trim();
      if (!normalizedPlaylistId || this.isPending(normalizedPlaylistId)) {
        return null;
      }

      const toast = useToastStore();
      this.setPending(normalizedPlaylistId, true);

      try {
        const result = await apiRequest(
          '/api/palette-logs/save.php',
          {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              playlist_id: Number(normalizedPlaylistId)
            })
          },
          '팔레트 로그 저장 처리에 실패했습니다.'
        );

        await this.load({ force: true, silent: true });
        toast.show(
          Boolean(result?.saved) ? '팔레트로그에 저장되었습니다' : '팔레트로그에서 삭제되었습니다'
        );

        return result;
      } finally {
        this.setPending(normalizedPlaylistId, false);
      }
    },

    clear() {
      this.paletteLogs = [];
      this.isLoaded = false;
      this.pendingMap = {};
    }
  }
});
