<script setup>
import AlertModal from '@/components/AlertModal.vue';
import ToastMessage from '@/components/ToastMessage.vue';
import { useAlertStore } from '@/stores/alert';
import { useToastStore } from '@/stores/toast';

const alert = useAlertStore();
const toast = useToastStore();
</script>

<template>
  <RouterView />
  <AlertModal
    :open="alert.open"
    :title="alert.title"
    :message="alert.message"
    :confirm-text="alert.confirmText"
    @close="alert.close"
  />
  <ToastMessage :open="toast.open" :message="toast.message" @close="toast.close" />
</template>
