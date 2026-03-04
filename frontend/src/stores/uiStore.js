import { defineStore } from 'pinia';
import { ref } from 'vue';

const DEFAULT_AVATAR_COLOR = '#B7AEA6';

function readCurrentUserColor() {
  try {
    const raw = localStorage.getItem('tone_current_user');
    if (!raw) return '';

    const user = JSON.parse(raw);
    return typeof user?.profileColor === 'string' ? user.profileColor : '';
  } catch {
    return '';
  }
}

function resolveInitialColor() {
  const userColor = readCurrentUserColor();
  if (userColor) return userColor;

  const legacyColor = localStorage.getItem('avatarColor');
  return legacyColor || DEFAULT_AVATAR_COLOR;
}

export const useUiStore = defineStore('ui', () => {
  const avatarColor = ref(resolveInitialColor());

  function persistAvatarColor(color) {
    localStorage.setItem('avatarColor', color);

    try {
      const raw = localStorage.getItem('tone_current_user');
      if (!raw) return;

      const user = JSON.parse(raw);
      if (!user || typeof user !== 'object') return;

      user.profileColor = color;
      localStorage.setItem('tone_current_user', JSON.stringify(user));
    } catch {
      // ignore localStorage parse error
    }
  }

  const setAvatarColor = (color) => {
    const nextColor =
      typeof color === 'string' && color.trim() ? color.trim() : DEFAULT_AVATAR_COLOR;

    avatarColor.value = nextColor;
    persistAvatarColor(nextColor);
  };

  const syncFromCurrentUser = () => {
    const userColor = readCurrentUserColor();
    if (userColor) {
      avatarColor.value = userColor;
      localStorage.setItem('avatarColor', userColor);
      return;
    }

    const legacyColor = localStorage.getItem('avatarColor');
    avatarColor.value = legacyColor || DEFAULT_AVATAR_COLOR;
  };

  const clearAvatarColor = () => {
    avatarColor.value = DEFAULT_AVATAR_COLOR;
    localStorage.removeItem('avatarColor');
  };

  return {
    avatarColor,
    setAvatarColor,
    syncFromCurrentUser,
    clearAvatarColor
  };
});
