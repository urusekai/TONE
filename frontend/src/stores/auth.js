import { computed, ref } from 'vue';
import { defineStore } from 'pinia';

const STORAGE_KEY = 'tone_current_user';
const DEFAULT_PROFILE_COLOR = '#B7AEA6';

function readStoredUser() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return null;

    const parsed = JSON.parse(raw);
    return parsed && typeof parsed === 'object' ? parsed : null;
  } catch {
    return null;
  }
}

export const useAuthStore = defineStore('auth', () => {
  const currentUser = ref(readStoredUser());

  const avatarColor = computed(() => {
    const color = currentUser.value?.profileColor;
    return typeof color === 'string' && color.trim() ? color : DEFAULT_PROFILE_COLOR;
  });

  function setCurrentUser(user) {
    if (!user || typeof user !== 'object') return;

    const nextUser = {
      ...user,
      profileColor:
        typeof user.profileColor === 'string' && user.profileColor.trim()
          ? user.profileColor
          : DEFAULT_PROFILE_COLOR
    };

    currentUser.value = nextUser;
    localStorage.setItem(STORAGE_KEY, JSON.stringify(nextUser));
  }

  function clearCurrentUser() {
    currentUser.value = null;
    localStorage.removeItem(STORAGE_KEY);
  }

  function setProfileColor(profileColor) {
    if (!currentUser.value || typeof profileColor !== 'string' || !profileColor.trim()) return;

    setCurrentUser({
      ...currentUser.value,
      profileColor
    });
  }

  async function syncMyProfile(fetchMyProfileFn) {
    if (typeof fetchMyProfileFn !== 'function') return;

    const result = await fetchMyProfileFn();
    if (result?.user) {
      setCurrentUser(result.user);
    }
  }

  return {
    currentUser,
    avatarColor,
    setCurrentUser,
    clearCurrentUser,
    setProfileColor,
    syncMyProfile
  };
});

