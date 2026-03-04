<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

defineOptions({
  name: 'AppHeader'
});

const route = useRoute();
const router = useRouter();
const avatarColor = ref('');

const headerType = computed(() => route.meta.headerType || 'logo');
const headerTitle = computed(() => route.meta.title || '');
const backTo = computed(() => route.meta.backTo || '/main');

const isBackHeader = computed(() => headerType.value === 'back-title');
const isLogoHeader = computed(() => !isBackHeader.value);
const showTitle = computed(
  () => headerType.value === 'back-title' || headerType.value === 'logo-title'
);

const headerClass = computed(() => {
  if (isBackHeader.value) {
    return ['header', 'header--back', 'header--title'];
  }
  return ['header', 'header--logo'];
});

function handleBackClick() {
  if (window.history.length > 1) {
    router.back();
    return;
  }

  router.push(backTo.value);
}

function loadAvatarColor() {
  try {
    const raw = localStorage.getItem('tone_current_user');
    if (!raw) {
      avatarColor.value = '';
      return;
    }

    const user = JSON.parse(raw);
    avatarColor.value = typeof user?.profileColor === 'string' ? user.profileColor : '';
  } catch {
    avatarColor.value = '';
  }
}

onMounted(() => {
  loadAvatarColor();
});

watch(
  () => route.fullPath,
  () => {
    loadAvatarColor();
  }
);
</script>

<template>
  <header :class="headerClass">
    <RouterLink v-if="isLogoHeader" class="header__left-btn header__left-btn--logo" to="/main">
      <img src="../assets/icons/logo.svg" alt="로고" />
    </RouterLink>
    <button
      v-else
      type="button"
      class="header__left-btn header__left-btn--back"
      @click="handleBackClick"
    >
      <img src="../assets/icons/prev.svg" alt="뒤로가기" />
    </button>

    <h1 v-if="showTitle" class="header__title">{{ headerTitle }}</h1>
    <div v-else class="header__title-spacer"></div>

    <RouterLink class="header__right-btn" to="/profile-edit">
      <div class="avatar" :style="{ backgroundColor: avatarColor || undefined }"></div>
    </RouterLink>
  </header>
</template>
