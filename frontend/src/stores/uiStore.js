import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useUiStore = defineStore('ui', () => {
  const avatarColor = ref(localStorage.getItem('avatarColor') || '#B7AEA6');

  const setAvatarColor = (color) => {
    avatarColor.value = color;
    localStorage.setItem('avatarColor', color);
  };

  return {
    avatarColor,
    setAvatarColor
  };
});
