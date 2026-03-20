import { apiRequest } from '@/services/httpClient';

let cachedDailyResult = null;
let cachedDailyKey = '';
let pendingDailyRequest = null;

function getTodayKey() {
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

export async function fetchDailyTone(options = {}) {
  const { force = false } = options;
  const todayKey = getTodayKey();

  if (!force && cachedDailyResult && cachedDailyKey === todayKey) {
    return cachedDailyResult;
  }

  if (!force && pendingDailyRequest) {
    return pendingDailyRequest;
  }

  pendingDailyRequest = apiRequest('/api/playlist/daily.php', {}, '오늘의 톤을 불러오지 못했습니다.')
    .then((result) => {
      cachedDailyResult = result;
      cachedDailyKey = String(result?.date || todayKey);
      return result;
    })
    .finally(() => {
      pendingDailyRequest = null;
    });

  return pendingDailyRequest;
}

export function clearDailyToneCache() {
  cachedDailyResult = null;
  cachedDailyKey = '';
  pendingDailyRequest = null;
}
