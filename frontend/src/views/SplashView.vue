<template>
  <main id="splash">
    <img class="logo" src="@/assets/icons/logo-full.svg" alt="로고" />
  </main>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

onMounted(() => {
  setTimeout(() => {
    router.replace('/main');
  }, 2000);
});
</script>

<style>
#splash {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>
