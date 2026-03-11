import { useAlertStore } from '@/stores/alert';

export function showAlert(message, options) {
  const alert = useAlertStore();
  alert.show(message, options);
}
