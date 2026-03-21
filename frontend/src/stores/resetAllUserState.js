import { useAlertStore } from '@/stores/alert';
import { useAuthStore } from '@/stores/auth';
import { useCalendarStore } from '@/stores/calendarStore';
import { useDailySpectrumStore } from '@/stores/dailySpectrum';
import { usePaletteLogStore } from '@/stores/paletteLog';
import { usePlayerStore } from '@/stores/player';
import { useToastStore } from '@/stores/toast';
import { clearDailyToneCache } from '@/services/dailyToneService';

const APP_LOCAL_STORAGE_KEYS = ['tone_current_user', 'tone_recent_tags_v1'];

export function resetAllUserState() {
  const authStore = useAuthStore();
  const dailySpectrumStore = useDailySpectrumStore();
  const playerStore = usePlayerStore();
  const paletteLogStore = usePaletteLogStore();
  const calendarStore = useCalendarStore();
  const alertStore = useAlertStore();
  const toastStore = useToastStore();

  authStore.clearCurrentUser();
  dailySpectrumStore.resetAll();
  playerStore.resetAll();
  clearDailyToneCache();

  if (typeof paletteLogStore.$reset === 'function') {
    paletteLogStore.$reset();
  }
  if (typeof calendarStore.$reset === 'function') {
    calendarStore.$reset();
  }
  if (typeof alertStore.$reset === 'function') {
    alertStore.$reset();
  }
  if (typeof toastStore.reset === 'function') {
    toastStore.reset();
  } else if (typeof toastStore.$reset === 'function') {
    toastStore.$reset();
  }

  sessionStorage.clear();
  APP_LOCAL_STORAGE_KEYS.forEach((key) => {
    localStorage.removeItem(key);
  });
}
